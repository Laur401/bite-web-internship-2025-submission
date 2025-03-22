<?php session_start();

/**
 * Handles session destruction if a POST destroy variable is set.
 * Starts a new one right afterwards.
 */
if (isset($_POST["destroy"])){ session_destroy(); session_start();}

/**
 * Initializes the integer array if it has not already been set in a session.
 * Reads the data from the input.txt file (or kills the script if not available),
 * sorts it and stores it a session variable.
 * Also puts the last (biggest) element into a separate variable for later manipulation.
 */
if (!isset($_SESSION["integerArray"])) {
    try {
        $integerArray = readFileToArray("../data/input.txt");
    } catch (Exception $e){
        error_log($e);
        $integerArray = array(0);
    }
    sort($integerArray);
    $_SESSION["integerArray"] = $integerArray;
    $_SESSION["inputElement"] = $integerArray[array_key_last($integerArray)];
}

/**
 * Puts the session variable references to other variables. Purely for decoration/easier reading.
 */
/** @var int[] $integerArray */
$integerArray =& $_SESSION["integerArray"];
/** @var int $inputElement */
$inputElement =& $_SESSION["inputElement"];

/**
 * Checks if a POST subtract/add variables are set, and if so, calls the appropriate function.
 */
if (isset($_POST["subtract"])){
    subtract($inputElement, 5);
}
if (isset($_POST["add"])){
    add($inputElement, 5);
}

/**
 * Adds a specified amount to the provided element.
 * @param int $element
 * @param int $count
 * @return void
 */
function add(int &$element, int $count): void
{
    $element += $count;
}

/**
 * Subtracts a specified amount from the provided element, making sure it does not go below 0.
 * @param int $element
 * @param int $count
 * @return void
 */
function subtract(int &$element, int $count): void
{
    $element -= $count;
    if ($element<0) { $element = 0; }
}

/**
 * @param string $file
 * @return array
 * @throws Exception
 */
function readFileToArray(string $file): array{
    $integerArray = @file($file, FILE_IGNORE_NEW_LINES);
    if ($integerArray === false) {
        $e = error_get_last()['message'];
        throw new Exception("Unable to open input.txt file. $e");
    }
    array_filter($integerArray, "is_numeric");
    return $integerArray;
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

    <form method='post' action=''>
        <?php
        echo "<input type='text' name='inp' value='$inputElement' disabled/>"
        ?>
        <button name="subtract" type="submit">-</button>
        <button name="add" type="submit">+</button>
        <button name="destroy" type="submit">Destroy Session</button>
    </form>
</div>
