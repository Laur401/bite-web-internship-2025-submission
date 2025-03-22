<?php session_start();

/**
 * Handles session destruction if a POST destroy variable is set.
 * Starts a new one right afterwards.
 */
if (isset($_POST["destroy"])){ session_destroy(); session_start();}

/**
 * Initializes the integer array if it has not already been set in a session.
 * Reads the data from the input.txt file (or if there is an exception serves a default 0 array),
 * sorts it and stores it a session variable.
 * Also puts the last (biggest) element into a separate variable for later manipulation.
 */
if (!isset($_SESSION["integerArray"])) {
    try {
        $integerArray = readFileToNumericArray(__DIR__ . "/../data/input.txt");
    } catch (Exception $e){
        error_log($e);
        $integerArray = array(0);
    }
    sort($integerArray);
    $_SESSION["integerArray"] = &$integerArray;
    $_SESSION["inputElementKey"] = array_key_last($integerArray);
    $_SESSION["inputElement"] = &$integerArray[$_SESSION["inputElementKey"]];
}

/**
 * Puts the session variable references to other variables. Purely for decoration/easier reading.
 */
/** @var int[] $integerArray */
$integerArray =& $_SESSION["integerArray"];
/** @var int $inputElement */
$inputElement =& $_SESSION["inputElement"];
/** @var int $inputElementKey */
$inputElementKey =& $_SESSION["inputElementKey"];

/**
 * Checks if a POST subtract/add variable is set, and if so, calls the appropriate function.
 */
if (isset($_POST["subtract"])){
    subtract($inputElement, 5);
    sortChangedNumber($integerArray, $inputElementKey);
}
if (isset($_POST["add"])){
    add($inputElement, 5);
    sortChangedNumber($integerArray, $inputElementKey);
}

/**
 * Adds a specified amount to the provided element.
 * @param int $element Element to add the amount onto.
 * @param int $count The amount to add to the element.
 * @return void
 */
function add(int &$element, int $count): void
{
    $element += $count;
}

/**
 * Subtracts a specified amount from the provided element, making sure it does not go below 0.
 * @param int $element Element to subtract the amount from.
 * @param int $count The amount to subtract from the element.
 * @return void
 */
function subtract(int &$element, int $count): void
{
    $element -= $count;
    if ($element<0) { $element = 0; }
}

/**
 * Reads a specified file and outputs it to an array, then filters out all non-numeric values.
 * @param string $file The file to read the input from.
 * @return array The array of values read from the file.
 * @throws Exception If specified file does not exist.
 */
function readFileToNumericArray(string $file): array {
    $integerArray = @file($file, FILE_IGNORE_NEW_LINES);
    if ($integerArray === false) {
        $e = error_get_last()['message'];
        throw new Exception("Unable to open file. $e");
    }
    array_filter($integerArray, "is_numeric");
    return $integerArray;
}

/**
 * Sorts the changed number into the array. Uses exponential search-based algorithm.
 * @param array $integerArray The array to sort in.
 * @param int $integerArrayElementKey The key of the number that is to be sorted.
 * @return void
 */
function sortChangedNumber(array &$integerArray, int &$integerArrayElementKey): void {
    // Helper function to move the element into a specified position in the array.
    function moveArrayElement(array $array, int $arrayElementKey, int $moveTo): array {
        $el = array_splice($array, $arrayElementKey, 1);
        $top = array_splice($array, 0, $moveTo);
        return array_merge($top, $el, $array);
    }
    /**
     * Helper function to determine whether the array key is in the correct place, needs to go left or needs to go right.
     * @param array $array The array to check.
     * @param int $arrayKey
     * @param int $comparisonValue
     * @param int $spec
     * @return int
     */
    function elementPosition(array $array, int $arrayKey, int $comparisonValue, int $spec = 0): int {
        if ($comparisonValue >= (@$array[$arrayKey-1]??PHP_INT_MIN) && $comparisonValue <= (@$array[$arrayKey+$spec]??PHP_INT_MAX)) {return 0;}
        if ($comparisonValue < (@$array[$arrayKey-1]??PHP_INT_MIN)) {return -1;} //Go left
        if ($comparisonValue >= (@$array[$arrayKey+$spec]??PHP_INT_MAX)){return 1;} //Go right
        throw new Exception("Something wrong in elementPosition function.");
    }

    $status = PHP_INT_MAX;
    $arrayPointer = $integerArrayElementKey;
    $stepsCounter = 1/2;
    $primer = 1; // Offset for initial search to avoid searching the searched object itself.
    while ($status !== 0)
    {
        $stepsCounter*=2;
        switch (elementPosition($integerArray, $arrayPointer, $integerArray[$integerArrayElementKey], $primer)){
            case 0:
                $status = 0;
                break;
            case -1:
                if ($status!==-1){
                    $stepsCounter = 1;
                    $status = -1;
                }
                $arrayPointer-=$stepsCounter;
                break;
            case 1:
                if ($status !== 1){
                    $stepsCounter = 1;
                    $status = 1;
                }
                $arrayPointer+=$stepsCounter;
                break;
        }
        $primer = 0;
        if ($arrayPointer >= count($integerArray)) {$arrayPointer = count($integerArray);}
        if ($arrayPointer < 0) {$arrayPointer = 0;}
    }

    if ($arrayPointer > $integerArrayElementKey) {$arrayPointer--;} //For off-by-one error from array slicing if inserting to the right of original position.
    $integerArray = moveArrayElement($integerArray, $integerArrayElementKey, $arrayPointer);
    $integerArrayElementKey = $arrayPointer;
}
?>
<link rel="stylesheet" href="css/style.css" type="text/css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Commissioner:wght@100..900&display=swap" rel="stylesheet">

<div class="container">
    <div class="header">Sorted list of numbers</div>
    <div class="numberList">
        <?php
        /**
         * Displays the sorted integer array by iterating through it and echoing each one.
         */
        foreach ($integerArray as $integer) {
            echo "<div>$integer</div>";
        }
        ?>
    </div>
    <div class="header">Highest number</div>

    <form class='control-form' method='post' action=''>
        <?php
        echo "<input type='text' name='inp' value='$inputElement' disabled/>"
        ?>
        <button class="button" name="subtract" type="submit">-</button>
        <button class="button" name="add" type="submit">+</button>
        <button class="button" name="destroy" type="submit">Destroy Session</button>
    </form>
</div>
