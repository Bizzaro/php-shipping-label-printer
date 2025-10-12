<?php

class TextualNumber
{
  public $string;
  public $number;
  private $data;

  private $linefeed;

  function __construct($string)
  {
    $this->string = $string;
    $this->linefeed = explode(",", $this->string);
    $this->loopData();
  }

  function loopData()
  {
    foreach ($this->linefeed as $key => $value) {
      $split = explode(".", $value);
      $rspart = "Dollars " . $this->numToWords($split[0]);
      $pspart = "";
      if (count($split) == 2) {
        $pspart = ($split[1] != "") ? " and " . $this->numToWords($split[1]) . " Cents" : "";
      }
      $this->data[] = $rspart . $pspart;
    }
  }

  function numToWords($number)
  {
    if (($number < 0) || ($number > 999999999)) {
      return "$number out of range";
    }

    $millions = floor($number / 1000000);  /* Millions */
    $number -= $millions * 1000000;

    $thousands = floor($number / 1000);  /* Thousands */
    $number -= $thousands * 1000;

    $hundreds = floor($number / 100);  /* Hundreds */
    $number -= $hundreds * 100;

    $tens = floor($number / 10);  /* Tens */
    $ones = $number % 10;  /* Ones */

    $res = "";

    if ($millions) {
      $res .= $this->numToWords($millions) . " Million";
    }

    if ($thousands) {
      $res .= (empty($res) ? "" : " ") . $this->numToWords($thousands) . " Thousand";
    }

    if ($hundreds) {
      $res .= (empty($res) ? "" : " ") . $this->numToWords($hundreds) . " Hundred";
    }

    $arr_ones = array(
      "",
      "One",
      "Two",
      "Three",
      "Four",
      "Five",
      "Six",
      "Seven",
      "Eight",
      "Nine",
      "Ten",
      "Eleven",
      "Twelve",
      "Thirteen",
      "Fourteen",
      "Fifteen",
      "Sixteen",
      "Seventeen",
      "Eighteen",
      "Nineteen"
    );

    $arr_tens = array(
      "",
      "",
      "Twenty",
      "Thirty",
      "Forty",
      "Fifty",
      "Sixty",
      "Seventy",
      "Eighty",
      "Ninety"
    );

    if ($tens || $ones) {
      if (!empty($res)) {
        $res .= " and ";
      }

      if ($tens < 2) {
        $res .= $arr_ones[$tens * 10 + $ones];
      } else {
        $res .= $arr_tens[$tens];
        if ($ones) {
          $res .= "-" . $arr_ones[$ones];
        }
      }
    }

    if (empty($res)) {
      $res = "Zero";
    }

    return trim($res);
  }

  function flushData()
  {
    print_r($this->data);
  }
}