<?php

declare(strict_types=1);

// Missing strict types declaration to trigger violations
// declare(strict_types=1);

// Global function - bad practice
function badly_named_function()
{
    global $globalVar; // Using global variables
    $unused_variable = 'test'; // Unused variable
    eval('echo "eval is dangerous";'); // Security violation
}

// Class with multiple violations
class temp_bad_class
{ // Bad class name (not PascalCase)
    public $publicProperty; // Untyped property
    private $x; // Non-descriptive name

    // Method with high complexity
    public function complexMethod($a, $b, $c, $d, $e, $f, $g, $h) // Too many parameters
    {if ($a) {
        if ($b) {
            if ($c) {
                if ($d) {
                    if ($e) {
                        if ($f) {
                            if ($g) {
                                if ($h) {
                                    echo 'too nested'; // High cyclomatic complexity
                                }
                            }
                        }
                    }
                }
            }
        }
    }

        // Long method
        $x = 1;
        $x++;
        $x++;
        $x++;
        $x++;
        $x++;

        // Using deprecated functions
        $size = sizeof([1,2,3]); // Should use count()

        // Unused variable
        $unused = 'never used';

        return $x;
    }

    // Method with too high cognitive complexity
    public function anotherBadMethod()
    {
        for ($i = 0; $i < 10; $i++) {
            for ($j = 0; $j < 10; $j++) {
                for ($k = 0; $k < 10; $k++) {
                    if ($i > 5 && $j > 5 && $k > 5 || $i < 2 || $j < 2 || $k < 2) {
                        echo "nested loops and complex conditions";
                    }
                }
            }
        }
    }
}

// Code after class - bad structure
$global_var = "bad"; // Snake case for variable

while (true) {
    new ConstructorPropertyPromotionSingleLineDocblockIndentOK();
    echo 'infinite loop';
    break; // Just to prevent actual infinite loop during analysis
}
