<?php
/**
 * o      |                              |         
 * .,---.-|-- ,---.,---..     .,---.,---.|,--,---.
 * ||   | |   |---'|    |  |  || o ||    |   `---.
 * ``   ' `-- `--  `    `--'--'`---'`    '`--`---'
 *
 * @author    Josh Varner <josh.varner@interworks.com>
 */

namespace app\views\helpers;

/**
 * Converts string or array of Greek letters to HTML entities
 */
class Greek extends \Zend_View_Helper_Abstract {
    public static $greekLettersRegex = '/^(alpha|beta|gamma|delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigma|tau|upsilon|phi|chi|psi|omega)$/i';

    public function __invoke($letters, array $options = array()) {
        extract($options + array(
            'filterNonLetters' => true,
            'joinDelim'        => '',
            'invert'           => false,
            'upperLower'       => 'upper', // Can be 'upper', 'lower', or 'both'
            'noSpan'           => false,
        ));

        if ($invert) {
            $filterNonLetters = false;
        }

        if (!is_array($letters)) {
            $flags = $filterNonLetters ? 0 : PREG_SPLIT_DELIM_CAPTURE;
            $letters = preg_split('/(\s+)/', $letters, null, $flags);
        }

        if ($invert) {
            $letters = preg_grep(static::$greekLettersRegex, $letters, PREG_GREP_INVERT);
            return $this->view->escape(implode($joinDelim, $letters));
        } else if ($filterNonLetters) {
            $letters = preg_grep(static::$greekLettersRegex, $letters);
            
            if (empty($letters)) {
                return '';
            }

            if ('both' !== $upperLower) {
                $letters = array_map('strtolower', $letters);
                
                if ('upper' === $upperLower) {
                    $letters = array_map('ucfirst', $letters);
                }
            }

            $letters = array_map(function ($val) { return "&{$val};"; }, $letters);

            return ($noSpan ? '' : '<span class="greek">') . implode($joinDelim, $letters) . ($noSpan ? '' : '</span>');
        }

        $letters = array_map(array($this->view, 'escape'), $letters);
        
        if ('both' !== $upperLower) {
            if ('upper' === $upperLower) {
                $func = function ($val) { return '&' . ucfirst(strtolower($val[0])) . ';'; };
            } else {
                $func = function ($val) { return '&' . strtolower($val[0]) . ';'; };
            }
            
            $letters = preg_replace_callback(static::$greekLettersRegex, $func, $letters);
        } else {
            $letters = preg_replace(static::$greekLettersRegex, '&$1;', $letters);
        }
        
        return implode($joinDelim, $letters);
    }
}
