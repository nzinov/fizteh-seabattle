<?php
function form($args)
{
$output =  "<form class=\"".val($args['form-class'],"form-horizontal")."\" action=\"index.php?page=".val($args['action'],"main")."\" method=\"".val($args['method'],"POST")."\" >";
$numargs = func_num_args();
$arg_list = func_get_args();
for ($i = 1; $i < $numargs; $i++) {
$output .= $arg_list[$i];
}
return  $output."<div class=\"form-actions\"><button ".(val($args['enabled'],true) ? "" : "disabled")." type=\"submit\" class=\"btn btn-primary\">Отправить</button><a type=\"button\" class=\"btn\" href=\"".val($args['reverse'],"index.php")."\">Отмена</a></div></form>";
}
function input_text($name, $label, $default = "",$enabled = true)
{
return "<div class=\"form-group\"><label for=\"$name\">$label</label><input ".($enabled ? "" : "disabled ")." class=\"form-control\" type=\"text\" id=\"$name\" name=\"$name\" placeholder=\"$label\" value=\"$default\"></div>";
}
function input_password($name, $label)
{
return "<div class=\"form-group\"><label for=\"$name\">$label</label><input class=\"form-control\" type=\"password\" id=\"$name\" name=\"$name\" placeholder=\"$label\"></div>";
}
function input_hidden($name, $value)
{
return "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
}
function input_textarea($name, $label, $default = "",$rows = 3,$enabled = true)
{
return "<div class=\"form-group\"><label for=\"$name\">$label</label><textarea class=\"form-control\" ".($enabled ? "" : "disabled ")." rows=\"$rows\" id=\"$name\" name=\"$name\">$default</textarea></div>";
}
function input_select($name, $label, $options, $default = 0,$enabled = true)
{
$output =  "<div class=\"form-group\"><label for=\"$name\">$label</label><select class=\"form-control\" ".($enabled ? "" : "disabled ")." id=\"$name\" name=\"$name\">";
for ($i = 0; $i < count($options);$i++)
{
if ($default == $i) $sel = "selected";
else $sel = "";
$output .=  "<option value=$i $sel>".$options[$i]."</option>";
}
$output .= "</select></div>";
return $output;
}
function input_select_key($name, $label, $options, $default = 0,$enabled = true)
{
$output =  "<div class=\"form-group\"><label for=\"$name\">$label</label><select class=\"form-control\" ".($enabled ? "" : "disabled ")." id=\"$name\" name=\"$name\">";
foreach ($options as $key => $value)
{
if ($default == $key) $sel = "selected";
else $sel = "";
$output .=  "<option value=$key $sel>".$value."</option>";
}
$output .= "</select></div>";
return $output;
}
function val($val, $def)
{
if (isset($val)) return $val;
else return $def;
}
function alert($text, $color)
{
echo "
<div class='alert alert-$color alert-dismissable'>
  <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
  $text
</div>
";
}
