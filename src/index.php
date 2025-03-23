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
    $integerArray = quickSort($integerArray);
    $_SESSION["integerArray"] = &$integerArray;
    $_SESSION["inputElementKey"] = array_key_last($integerArray);
    $_SESSION["inputElement"] = &$integerArray[$_SESSION["inputElementKey"]];
    unset($_POST);
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
if (isset($_POST["inp"]) && intval($_POST["inp"]) != $inputElement){
    $inputElement = intval($_POST["inp"]);
    sortChangedNumber($integerArray, $inputElementKey);
}
else {
    if (isset($_POST["subtract"])){
        subtract($inputElement, 5);
        sortChangedNumber($integerArray, $inputElementKey);
    }
    if (isset($_POST["add"])){
        add($inputElement, 5);
        sortChangedNumber($integerArray, $inputElementKey);
    }
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
        $e = error_get_last()['message'] ?? "";
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
    /**
     * Helper function to move the element into a specified position in the array.
     * @param array $array The array to manipulate.
     * @param int $arrayElementKey The key of the element that is to be moved.
     * @param int $moveTo The location to move the key to.
     * @return array The modified array with the element moved to the correct location.
     */
    $move_array_element = function (array $array, int $arrayElementKey, int &$moveTo): array {
        if ($arrayElementKey < $moveTo) {$moveTo--;} // For off-by-one error from array slicing if inserting to the right of original position.
        $el = array_splice($array, $arrayElementKey, 1);
        $top = array_splice($array, 0, $moveTo);
        return array_merge($top, $el, $array);
    };
    /**
     * Helper function to check whether the comparison value would fit in the location of an ascending-value-sorted array
     * with the arrayKey or if the arrayKey needs to decrease or increase.
     * @param array $array The array to check.
     * @param int $arrayKey The location key of the array value to check.
     * @param int $comparisonValue The value that would be inserted into the location.
     * @param int $spec Initializer value to avoid checking the comparison value itself if it exists in the array.
     * @return int 0 if location is correct, -1 if the key needs to decrease, 1 if the key needs to increase.
     */
    $element_position = function (array $array, int $arrayKey, int $comparisonValue, int $spec = 0): int {
        //if ($comparisonValue >= (@$array[$arrayKey-1]??PHP_INT_MIN) && $comparisonValue < (@$array[$arrayKey+$spec]??PHP_INT_MAX)) {return 0;}
        if ($comparisonValue < (@$array[$arrayKey-1]??PHP_INT_MIN)) {return -1;} //Go left
        if ($comparisonValue >= (@$array[$arrayKey+$spec]??PHP_INT_MAX)){return 1;} //Go right
        return 0;
    };

    $status = PHP_INT_MAX;
    $arrayPointer = $integerArrayElementKey;
    $stepsCounter = 1/2;
    $primer = 1; // Offset for initial search to avoid searching the searched object itself.
    while ($status !== 0)
    {
        $stepsCounter*=2;
        switch ($element_position($integerArray, $arrayPointer, $integerArray[$integerArrayElementKey], $primer)){
            case 0:
                $status = 0;
                break;
            case -1:
                if ($status !== -1){
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
        // If the arrayPointer goes out of bounds, reset back to the bounds.
        if ($arrayPointer >= count($integerArray)) {$arrayPointer = count($integerArray);}
        if ($arrayPointer < 0) {$arrayPointer = 0;}
    }

    $integerArray = $move_array_element($integerArray, $integerArrayElementKey, $arrayPointer);
    $integerArrayElementKey = $arrayPointer;
}

/**
 * My implementation of the QuickSort algorithm. Uses a random pivot point to try to minimize the worst case scenario
 * effects.
 * @param array $array Input array
 * @return array Sorted array
 */
function quickSort(array $array): array{
    $arraySize = count($array);
    if ($arraySize<=1) return $array;

    $pivotKey = mt_rand(0, $arraySize-1);
    $pivot = $array[$pivotKey];
    $left = $right = array();
    for ($i=0;$i<$arraySize;$i++){
        if ($i===$pivotKey) {continue;}
        if ($array[$i]<=$pivot)
            $left[] = $array[$i];
        else
            $right[] = $array[$i];
    }
    return array_merge(quickSort($left), [$pivot], quickSort($right));
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
        echo "<input type='text' inputmode='numeric' name='inp' value='$inputElement'/>"
        ?>
        <button class="button" name="subtract" type="submit">-</button>
        <button class="button" name="add" type="submit">+</button>
        <button class="button" name="destroy" type="submit">Destroy Session</button>
    </form>
</div>

<script type="text/javascript"> // Disables submission of the form if page is refreshed.
    if (window.history.replaceState){
        window.history.replaceState(null, null, window.location.href);
    }
</script>

