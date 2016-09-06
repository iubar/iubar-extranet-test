<?php


function f1(){    
   $b = 'OK';
   throw new Exception("Situazione imprevista");
   return $b;
}

function p1(){
    $b = f1(); // PHP Fatal error:  Uncaught exception 'Exception' with message 'Situazione imprevista'
    // The code below will never be executed !
    echo "b: " . $b . PHP_EOL; 
}

function p2(){
    try {
        $b = f1();
    } catch (Exception $e) {
        echo "Si e' verificata un'eccezione: " . $e->getMessage() . PHP_EOL;
        // echo "b: " . $b . PHP_EOL; // PHP Notice: 'Undefined vairiable: b'
    }
   
    
}

p2();