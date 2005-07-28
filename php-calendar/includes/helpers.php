<?php

/*
function array_cons($x, $xs)
{
        array_unshift($xs, $x);
	return $xs;
}

function array_head($array)
{
	return $array[0];
}

function array_tail($array)
{
	array_shift($array);
	return $array;
}

function array_append($xs, $x)
{
	$xs[] = $x;
	return $xs;
}
*/

// this function returns the names from all of the input tags in $tag
// recursively
function get_input_names($tag)
{
        $names = array();

        if(is_array($tag)) {
                foreach($tag as $child) {
                        $names = array_merge($names, get_input_names($child));
                }
        } elseif(is_a($tag, 'Html')) {
                if($tag->tagName == 'input') {
                        foreach($tag->attributeList->list as $attr) {
                                $num = preg_match('/([^=])=[\'"](.*)[\'"]/',
                                                $attr, $matches);
                                $attr_name = $matches[1];
                                $attr_value = $matches[2];
                                if($attr_name == 'name') {
                                        $names[] = $attr_value;
                                }
                        }
                }

                foreach($tag->childElements as $child) {
                        $names = array_merge($names, get_input_names($child));
                }
        }

        return $names;
}

?>
