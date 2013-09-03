<?php

namespace app\models;

use app\extensions\security\Role;
use app\models\member\Update;
use vox\core\Registry;
use vox\net\Mail;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use DateTime;

/** @Entity
    @HasLifecycleCallbacks
    @Table(name="members", indexes={
        @Index(name="cachedName_idx", columns={"cachedName"})
    })
*/
class Member extends \app\models\User {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /** @Column(type="string", length=255) */
    protected $email = '';
    /** @Column(type="integer") */
    protected $status = 0;
    /** @Column(type="datetime") */
    protected $created;
    /** @Column(type="datetime") */
    protected $updated;
    /** @Column(type="string", length=512) */
    protected $cachedName = '';
    /** @Column(type="string", length=255) */
    protected $salutation = '';
    /** @Column(type="string", length=255) */
    protected $firstName = '';
    /** @Column(type="string", length=255) */
    protected $middleName = '';
    /** @Column(type="string", length=255) */
    protected $lastName = '';
    /** @Column(type="string", length=255) */
    protected $goesBy = '';
    /** @Column(type="string", length=255) */
    protected $maidenName = '';
    /** @Column(type="string", length=255) */
    protected $address1 = '';
    /** @Column(type="string", length=255) */
    protected $address2 = '';
    /** @Column(type="string", length=255) */
    protected $city = '';
    /** @Column(type="string", length=255) */
    protected $state = '';
    /** @Column(type="string", length=255) */
    protected $zipcode = '';
    /** @Column(type="string", length=255) */
    protected $country = '';
    /** @Column(type="integer") */
    protected $isParentsAddress = 0;
    /** @Column(type="string", length=255) */
    protected $permAddress1 = '';
    /** @Column(type="string", length=255) */
    protected $permAddress2 = '';
    /** @Column(type="string", length=255) */
    protected $permCity = '';
    /** @Column(type="string", length=255) */
    protected $permState = '';
    /** @Column(type="string", length=255) */
    protected $permZipcode = '';
    /** @Column(type="string", length=255) */
    protected $permCountry = '';
    /** @Column(type="string", length=255) */
    protected $imName = '';
    /** @Column(type="string", length=255) */
    protected $permanentEmail = '';
    /** @Column(type="string", length=255) */
    protected $homePhone = '';
    /** @Column(type="string", length=255) */
    protected $workPhone = '';
    /** @Column(type="string", length=255) */
    protected $cellPhone = '';
    /** @Column(type="string", length=255) */
    protected $faxNumber = '';
    /** @Column(type="string", length=255) */
    protected $memberId = '';
    /** @Column(type="string", length=255) */
    protected $transfer = '';
    /** @Column(type="date", nullable=true) */
    protected $graduationDate = null;
    /** @Column(type="string", length=100) */
    protected $instrument = '';
    /** @Column(type="string", length=255) */
    protected $profession = '';
    /** @Column(type="text") */
    protected $notes = '';
    /** @Column(type="text") */
    protected $transferNotes = '';
    /** @Column(type="string", length=50) */
    protected $podiumSubscription = '';

    protected $_hiddenFields = array('memberships', 'passwordHash', 'submissions', 'passwordResetToken', 'passwordResetExpireDate', 'lastPasswordChange');
    protected $_currentMemberships = false;
    protected $_futureMemberships = false;
    protected $_futureAndCurrentMemberships = false;
    protected $_currentChapterMemberships = false;
    protected $_pastChapterMemberships = false;
    protected $_memberUpdate = false;

    public function __construct() {
        $now = new DateTime();
        $this->setCreated(clone $now);
        $this->setUpdated($now);
    }

    protected function _onPropertyChanged($key, $oldValue, $newValue) {
        parent::_onPropertyChanged($key, $oldValue, $newValue);

        if (in_array($key, array('firstName', 'goesBy', 'middleName', 'maidenName', 'lastName'))) {
            $nameParts = $this->get(array('firstName','goesBy', 'middleName', 'maidenName', 'lastName'));
            $nameParts[$key] = $newValue;
            $cachedName = trim(implode(' ', array_filter($nameParts)));
            
            if ($this->cachedName !== $cachedName) {
                $this->setCachedName($cachedName);
            }
        }
    }

    // protected function _onPropertyChanged($key, $oldValue, $newValue) {
    //     if (!$this->_memberUpdate) {
    //         $em = static::getEm();
    //         $update = new Update();
    //         $update->setMember($this);
    //         $em->persist($update);
    //         $this->_memberUpdate = $update;
    //     }
    //
    //     $this->_memberUpdate->addDetail($key, $oldValue, $newValue);
    //     parent::_onPropertyChanged($key, $oldValue, $newValue);
    // }

    public function getDirectAssignedRoles() {
        $roles = parent::getRoles();
        sort($roles);
        return array_unique($roles);
    }

    public function getRoles() {
        $roles = parent::getRoles();
        
        foreach ($this->getCurrentMemberships() as $membership) {
            if ($membership->getChapter()) {
                continue;
            }

            $position = $membership->getPosition();

            if ($position) {
                foreach ($position->getRoles() as $role) {
                    $roles[] = $role;
                }
            }
        }

        foreach ($this->getChapterRoles() as $chapRoles) {
            foreach ($chapRoles as $role) {
                $roles[] = $role;
            }
        }

        sort($roles);
        return array_unique($roles);
    }

    public function getChapterRoles() {
        $chapters = array();
        
        foreach ($this->getCurrentChapterMemberships() as $membership) {
            $position = $membership->getPosition();

            if (!$position) {
                continue;
            }

            $chapter = $membership->getChapter();
            $chapterId = $chapter->getId();
            
            if (!isset($chapters[$chapterId])) {
                $chapters[$chapterId] = array();
            }

            foreach ($position->getRoles() as $role) {
                $chapters[$chapterId][] = $role;
            }
            
            $chapters[$chapterId] = array_unique($chapters[$chapterId]);
        }

        $lastMemberships = array();

        foreach ($this->getPastChapterMemberships() as $membership) {
            $position = $membership->getPosition();

            if (!$position) {
                continue;
            }

            $chapter = $membership->getChapter();
            $chapterId = $chapter->getId();
            $posName = $position->getName();

            if (!isset($lastMemberships[$chapterId])) {
                $lastMemberships[$chapterId] = $chapter->getLastMembershipForAllPositions();
            }
            
            if (isset($lastMemberships[$chapterId][$posName])) {
                if ($membership->getId() === $lastMemberships[$chapterId][$posName]->getId()) {
                    if (!isset($chapters[$chapterId])) {
                        $chapters[$chapterId] = array();
                    }

                    foreach ($position->getRoles() as $role) {
                        $chapters[$chapterId][] = $role;
                    }

                    $chapters[$chapterId] = array_unique($chapters[$chapterId]);
                }
            }
        }

        return $chapters;
    }
    
    public function getChapterPermissions($permissionAsKey = false) {
        $chapters = $this->getChapterRoles();

        if (!$permissionAsKey) {
            foreach ($chapters as &$roles) {
                $roles = Role::getPermissions($roles);
            }

            return $chapters;
        }
        
        $permissions = array();
        
        foreach ($chapters as $chapterId => $roles) {
            foreach (Role::getPermissions($roles) as $perm => $val) {
                if (!isset($permissions[$perm])) {
                    $permissions[$perm] = array();
                }
                
                $permissions[$perm][] = $chapterId;
                $permissions[$perm] = array_unique($permissions[$perm]);
            }
        }
        
        return $permissions;
    }
    
    public function getChaptersByPermission($permission) {
        $perms = $this->getChapterPermissions(true);
        
        if (isset($perms[$permission]) && !empty($perms[$permission])) {
            return Chapter::findBy(array('id' => $perms[$permission]));
        }
        
        return array();
    }
    
    public static function getNameById($id) {
        $em = static::getEm();
        
        $qs = "SELECT m.firstName, m.middleName, m.lastName FROM app\models\Member m
               WHERE m.id = :id
               ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('id' => $id));
        $row = $q->getResult();
        $fullName = '';
        
        foreach($row as $name) {
            foreach($name as $piece) {
                $fullName .= $piece.' ';
            }
        }
        
        $fullName = trim($fullName);
         
        return $fullName;
    }
    
    // returns the 1st entry based on chapter and position, where membership is current
    public static function getMemberInfoByChapterAndPosition($chapterId, $positionId) {
        $em = static::getEm();
        
        $qs = "SELECT m, mb FROM app\models\Membership mb
               JOIN mb.member m
               WHERE mb.chapter = :chapterId 
                   AND mb.position = :positionId
                   AND (mb.endDate > :today OR mb.endDate IS NULL)";
        $q = $em->createQuery($qs);
        $q->setMaxResults(1);
        $q->setParameters(array('chapterId' => $chapterId,
                                'positionId' => $positionId,
                                'today' => new \DateTime(),
                         ));
                    
        $row = $q->getResult();
        $memberInfo = array();
        if (!empty($row)) {
            $memberInfo['name'] = $row[0]->getMember()->getFullName();
            $memberInfo['email'] = $row[0]->getMember()->getEmail();
        }

        return $memberInfo;
    }
    
    public static function getMemberInfoByEmail($email) {
        $em = static::getEm();
        
        $qs = "SELECT m FROM app\models\Member m
               WHERE m.email = :email 
                   AND m.status = 0";
        $q = $em->createQuery($qs);
        $q->setMaxResults(1);
        $q->setParameters(array('email' => $email));
                    
        $row = $q->getResult();
        
        return $row;
    }
    
    
    public function getName() {
        return trim("{$this->firstName} {$this->lastName}");
    }
    
    public function getNameWithMiddle() {
        $middle = $this->middleName;

        if (1 === strlen($middle)) {
            $middle .= '.';       
        } 
        
        return implode(' ', array_filter(array_map('trim', array(
           $this->firstName, $middle, $this->lastName,
        ))));
    }
    
    public function getFullName() {
        $maidenName = '';

        if ($this->maidenName) {
            $maidenName = '(' . $this->maidenName . ')';
        }

        $middle = $this->middleName;

        if (1 === strlen($middle)) {
            $middle .= '.';
        }

        return implode(' ', array_filter(array_map('trim', array(
            $this->salutation, $this->firstName, $middle, $maidenName, $this->lastName,
        ))));
    }
    
    /**
     * Test if a user should be allowed to perform the specified action
     *
     * This is currently really basic, but will be fleshed out with
     * more specific logic later.
     */
    public function targetAllowed($user, $action = 'view') {
        if (!$user->isAllowed("member_{$action}")) {
            return false;
        }

        // If the user isn't a super user, make sure the user is an officer in a chapter that
        // this member is also a member of
        if ('view' === $action && !$user->isAllowed('member_view_all')) {
            $allowedChapters = $user->getChaptersByPermission('member_view');

            foreach ($this->getMemberships() as $membership) {
                if (!$membership->getChapter()) {
                    continue;
                }

                foreach ($allowedChapters as $allowedChapter) {
                    if ($allowedChapter->getId() === $membership->getChapter()->getId()) {
                        return true;
                    }
                }

            }

            return false;
        }

        return true;
    }
    
    public function getCurrentOrganizationNames() {
        $orgs = array();
        
        foreach ($this->getCurrentMemberships() as $membership) {
            $org = $membership->getTopOrganization();
            
            if ($org) {
                $org = $org->getName();
                
                if (!isset($orgs[$org])) {
                    $orgs[$org] = 1;
                }
            }
        }
        
        $orgs = array_keys($orgs);
        sort($orgs);
        return $orgs;
    }
    
    public function inOrganization($name) {
        $name = strtolower($name);

        foreach ($this->getCurrentMemberships() as $membership) {
            $org = $membership->getTopOrganization();
            
            if ($org && $name === strtolower($org->getName())) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getCurrentChapterMemberships() {
        if (false !== $this->_currentChapterMemberships) {
            return $this->_currentChapterMemberships;
        }
        
        $this->_currentChapterMemberships = array();
        
        foreach ($this->getCurrentMemberships() as $membership) {
            if (!$membership->getChapter()) {
                continue;
            }
            
            $this->_currentChapterMemberships[] = $membership;
        }
        
        return $this->_currentChapterMemberships;
    }
    
    public function getPastChapterMemberships() {
        if (false !== $this->_pastChapterMemberships) {
            return $this->_pastChapterMemberships;
        }
        
        $this->_pastChapterMemberships = array();
        
        foreach ($this->getPastMemberships() as $membership) {
            if (!$membership->getChapter()) {
                continue;
            }
            
            $this->_pastChapterMemberships[] = $membership;
        }
        
        return $this->_pastChapterMemberships;
    }
    
    public function getCurrentMemberships() {
        if (false !== $this->_currentMemberships) {
            return $this->_currentMemberships;
        }

        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.startDate <= CURRENT_TIMESTAMP())
                AND (m.endDate IS NULL OR m.endDate > CURRENT_TIMESTAMP())
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this));
        // $q->useResultCache(true);
        
        return $this->_currentMemberships = $q->getResult();
    }
    
    public function getCurrentMembershipsByPositionName($position) {
        if (false !== $this->_currentMemberships) {
            return $this->_currentMemberships;
        }

        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (p.name = :position)
                AND (m.startDate <= CURRENT_TIMESTAMP())
                AND (m.endDate IS NULL OR m.endDate > CURRENT_TIMESTAMP())
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this, 'position' => $position));
        // $q->useResultCache(true);
        
        return $this->_currentMemberships = $q->getResult();
    }
    
    public function getCurrentAndFutureMemberships() {
        if (false !== $this->_futureAndCurrentMemberships) {
            return $this->_futureAndCurrentMemberships;
        }

        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.endDate IS NULL OR m.endDate > CURRENT_TIMESTAMP())
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this));
        // $q->useResultCache(true);
        
        return $this->_futureAndCurrentMemberships = $q->getResult();
    }
    
    
    public function getFutureMemberships() {
        if (false !== $this->_futureMemberships) {
            return $this->_futureMemberships;
        }

        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.startDate > CURRENT_TIMESTAMP())
                AND (m.endDate IS NULL OR m.endDate > CURRENT_TIMESTAMP())
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this));
        // $q->useResultCache(true);
        
        return $this->_futureMemberships = $q->getResult();
    }
    
    // returns a member's memberships based on the chapter id
    public function getCurrentMembershipsByChapter($chapterId) {
        if (false !== $this->_currentMemberships) {
            return $this->_currentMemberships;
        }

        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND c.id = :chapterId
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.startDate <= CURRENT_TIMESTAMP())
                AND (m.endDate IS NULL OR m.endDate > CURRENT_TIMESTAMP())
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this, 'chapterId' => $chapterId));
        // $q->useResultCache(true);
        
        return $this->_currentMemberships = $q->getResult();
    }
    
    public function getMostRecentMembershipsByChapter($chapterId) {
        if (false !== $this->_currentMemberships) {
            return $this->_currentMemberships;
        }

        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND c.id = :chapterId
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.startDate <= CURRENT_TIMESTAMP())
            ORDER BY m.endDate DESC
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this, 'chapterId' => $chapterId));
        // $q->useResultCache(true);
        
        return $this->_currentMemberships = $q->getResult();
    }
    
    public function getCurrentMembershipsByOrganization($organizationId) {
        if (false !== $this->_currentMemberships) {
            return $this->_currentMemberships;
        }
   
        $em = static::getEm();
        
        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            LEFT JOIN d.organization o
            WHERE m.member = :member
                AND m.organization = :organizationId
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.endDate IS NULL OR m.endDate > CURRENT_TIMESTAMP())
        ";
        
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this, 'organizationId' => $organizationId));
        // $q->useResultCache(true);
        return $this->_currentMemberships = $q->getResult();
    }

    
    
    
    public function getPastMemberships() {
        $em = static::getEm();

        $qs = "SELECT m, p, c, d FROM app\models\Membership m
            LEFT JOIN m.position p 
            LEFT JOIN m.chapter c
            LEFT JOIN c.district d
            WHERE m.member = :member
                AND (c.status IS NULL OR c.status != 'Deleted')
                AND (m.startDate <= CURRENT_TIMESTAMP())
                AND (m.endDate IS NOT NULL)
                AND (m.endDate <= CURRENT_TIMESTAMP())
        ";
        $q = $em->createQuery($qs);
        $q->setParameters(array('member' => $this));
        // $q->useResultCache(true);

        return $q->getResult();
    }
    
    public function findDraftByType($type) {
        $em = static::getEm();
        $q = $em->createQuery("SELECT s FROM app\models\Submission s WHERE s.author = :user AND s.type = :type AND s.status = 'Draft'");
        $q->setMaxResults(1);
        $result = $q->setParameters(array(
            'user' => $this,
            'type' => $type,
        ));
        return $q->getOneOrNullResult();        
    }
    
    public static function getInstrumentList() {
        $q = static::getEm()->createQuery('SELECT DISTINCT m.instrument FROM app\models\Member m ORDER BY m.instrument');
        // $q->useResultCache(true);
        $rows = $q->getResult();
        $instruments = array();
        
        foreach ($rows as $row) {
            foreach ((array) $row['instrument'] as $instrument) {
                $instruments[] = $instrument;
            }
        }
        
        sort($instruments);
        $instruments = array_unique(array_filter($instruments));
        
        return $instruments;
    }

    public function toArray($shallow = false) {
        $arr = parent::toArray();
        unset($arr['memberships']);
        return $arr;
    }

    /**
     * @PrePersist @PreUpdate
     */
    public function setPasswordDatesIfNull() {
        if (null === $this->lastPasswordChange) {
            $this->setLastPasswordChange(new DateTime('@0'));
        }
        
        if (null === $this->passwordResetExpireDate) {
            $this->setPasswordResetExpireDate(new DateTime('@0'));
        }
    }

    /** @PreUpdate */
    public function updateTimestampsPreUpdate() {
        $this->setUpdated(new DateTime('now'));
    }

    public function sendWelcomeEmail() {
        $resetToken = $this->getPasswordResetToken();
        $memberName = htmlentities($this->getFullName());
        $resetUrl = "http://online.kkytbs.org/auth/reset-password/token/{$resetToken}";
        $resetUrl = htmlentities($resetUrl);

        $body =<<<ENDOFRESETEMAIL
<html>
<head>
<style type="text/css">
body { font: 13px/normal Arial, Helvetica, sans-serif; }
</style>
<body>
<p>Dear {$memberName},</p>

<p>
    Congratulations on your recent membership in Kappa Kappa Psi or Tau Beta Sigma! 
</p>
<p>
    An account has been created for you in the Kappa Kappa Psi / Tau Beta Sigma
    Online System.  However, we need you to verify your information and set your
    password.  Please click <a href="{$resetUrl}">this link</a> to confirm your account.
</p>
<p>
    From your account, you will be able to view your membership history and
    update your contact information.  Chapter Officers will be able to utilize
    the Online System to complete all required reports for National
    Headquarters.
</p>
<p>
    Should you have any problems or discover any errors, please contact the HQ
    Project Officer, Aaron Moore (hqacc@kkytbs.org or 405.372.2333) for
    assistance.
</p>
</body>
</html>
ENDOFRESETEMAIL;
    
        $bodyText =<<<ENDOFRESETEMAIL
Dear {$memberName},

Congratulations on your recent membership in Kappa Kappa Psi or Tau Beta Sigma! 

An account has been created for you in the Kappa Kappa Psi / Tau Beta Sigma Online System.  However, we need you to verify your information and set your password.  Please go to {$resetUrl} to confirm your account.

From your account, you will be able to view your membership history and update your contact information.  Chapter Officers will be able to utilize the Online System to complete all required reports for National Headquarters.

Should you have any problems or discover any errors, please contact the HQ Project Officer, Aaron Moore (hqacc@kkytbs.org or 405.372.2333) for assistance.

ENDOFRESETEMAIL;

        $from = 'noreply@kkytbs.org';
        $fromName = 'KKY/TBS Online';

        /** @todo Centralize these common mail options/settings */
        $mail = new Mail(array(
            'from'       => array($from, $fromName),
            'returnPath' => $from,
            'to'         => array(array($this->getEmail(), $this->getFullName())),
            'subject'    => 'Account Created',
            // 'body'       => $body,
            'bodyText'   => $bodyText,
        ));
        $mail->send();
    }

    // 
    // /** @PreUpdate */
    // public function onUpdate() {
    //     if (null === $this->lastPasswordChange) {
    //         $this->setLastPasswordChange(new DateTime('@0'));
    //     }
    //     
    //     if (null === $this->passwordResetExpireDate) {
    //         $this->setPasswordResetExpireDate(new DateTime('@0'));
    //     }
    // }
}