<?php

/**
 * Fork de Generic_Sniffs_Metrics_NestingLevelSniff
 * Autorisation de certaines méthodes à être au delà de $nestingLevel
 */
class Steevanb_Sniffs_Metrics_NestingLevelSniff implements PHP_CodeSniffer_Sniff
{
    /** @var string[] */
    protected static $allowedNestingLevelMethods = [];

    public static function addAllowedNestingLevelMethods(string $fileName, string $method)
    {
        static::$allowedNestingLevelMethods[] = $fileName . '::' . $method;
    }

    /**
     * A nesting level higher than this value will throw a warning.
     *
     * @var int
     */
    public $nestingLevel = 5;

    /**
     * A nesting level higher than this value will throw an error.
     *
     * @var int
     */
    public $absoluteNestingLevel = 10;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_FUNCTION];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Ignore abstract methods.
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        // Detect start and end of this function definition.
        $start = $tokens[$stackPtr]['scope_opener'];
        $end   = $tokens[$stackPtr]['scope_closer'];

        $nestingLevel = 0;

        // Find the maximum nesting level of any token in the function.
        for ($i = ($start + 1); $i < $end; $i++) {
            $level = $tokens[$i]['level'];
            if ($nestingLevel < $level) {
                $nestingLevel = $level;
            }
        }

        // We subtract the nesting level of the function itself.
        $nestingLevel = ($nestingLevel - $tokens[$stackPtr]['level'] - 1);

        if ($nestingLevel > $this->absoluteNestingLevel) {
            $error = 'Function\'s nesting level (%s) exceeds allowed maximum of %s';
            $data  = [
                $nestingLevel,
                $this->absoluteNestingLevel,
            ];
            $phpcsFile->addError($error, $stackPtr, 'MaxExceeded', $data);
        } elseif ($nestingLevel > $this->nestingLevel) {
            if (in_array(
                basename($phpcsFile->getFilename())
                    . '::'
                    . $tokens[$phpcsFile->findNext(T_STRING, $stackPtr)]['content'],
                static::$allowedNestingLevelMethods
            ) === false) {
                $warning = 'Function\'s nesting level (%s) exceeds %s; consider refactoring the function';
                $data = [$nestingLevel, $this->nestingLevel];
                $phpcsFile->addWarning($warning, $stackPtr, 'TooHigh', $data);
            }
        }
    }
}
