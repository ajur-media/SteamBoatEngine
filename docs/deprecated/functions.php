<?php

function get_decorated_diff($old, $new){
    $from_start = strspn($old ^ $new, "\0");
    $from_end = strspn(strrev($old) ^ strrev($new), "\0");

    $old_end = strlen($old) - $from_end;
    $new_end = strlen($new) - $from_end;

    $start = substr($new, 0, $from_start);
    $end = substr($new, $new_end);
    $new_diff = substr($new, $from_start, $new_end - $from_start);
    $old_diff = substr($old, $from_start, $old_end - $from_start);

    $new = "$start<ins style='background-color:#ccffcc'>$new_diff</ins>$end";
    $old = "$start<del style='background-color:#ffcccc'>$old_diff</del>$end";
    return array("old"=>$old, "new"=>$new);
}

function br2nl($input) {
    return preg_replace('/<br\s?\/?>/ius', "\n", str_replace("\n","",str_replace("\r","", htmlspecialchars_decode($input))));
}

