<?php 

function BinaryRepresentation(string $char):string
//returns the binary representation of a one byte string
{
    $binary = decbin(ord($char)); //convert byte to int and get binary representation
    $binary = str_pad($binary, 8, 0, STR_PAD_LEFT); //pad with 0s up to 8 digits
    return $binary;
}

function ReverseBits(string $byte):string
//gets a one byte string and returns a one byte string with the bits reversed
{
    $binary = BinaryRepresentation($byte); //get a string with the bits
    $binary = strrev($binary); //reverse the string
    $reversednumber = bindec($binary); //assemble the bits back into a number
    $reversed = pack("C",$reversednumber); //convert the number to a one byte string
    return $reversed;
    
}

function ServeContent(string $filename, string $content):void
{
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . strlen($content));
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $content;
}

function ServeModifiedFirmware(string $firmwarePath, string $wavetablePath, string $hexAddress):void
{
    //load the firmware into memory as a string
    $firmwarefhandler = fopen($firmwarePath, "rb");
    $firmwareSize = filesize($firmwarePath);
    $firmwareContents = fread($firmwarefhandler,$firmwareSize);
    //same for replacement
    $wavetablefhandler = fopen($wavetablePath, "rb");
    $wavetableSize = filesize($wavetablePath);
    $wavetableContents = fread($wavetablefhandler,$wavetableSize);
    $wavetableLength = strlen($wavetableContents);
    //loop through all the bytes in the firmware and replace the wavetable portion
    $replaceStart = hexdec($hexAddress);
    $replaceEnd = $replaceStart + $wavetableLength;
    $outputFirmware = "";
    for ($i = 0; $i < strlen($firmwareContents); $i++) {
        $char = $firmwareContents[$i];
        if($i < $replaceStart || $i > $replaceEnd)
        //we're copying a byte outside the replacment boundaries
        {
            $outputFirmware .= $char;
        }
        else
        {
            //at the correct point, inject the wavetable while inverting the bits
            $wavetableCharPos = $i-$replaceStart;
            $outputFirmware .= ReverseBits($wavetableContents[$wavetableCharPos]);
        }
    }
    ServeContent("custom_firmware.jic",$outputFirmware);
}

//$uploadsDir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
$wavetable = $_FILES["rawAudio"]["tmp_name"];
$firmware = $_FILES["firmwareFile"]["tmp_name"];
ServeModifiedFirmware($firmware, $wavetable, $_POST["offset"]);
?>