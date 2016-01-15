<?php
class CNavigation {
  public static function GenerateMenu($items, $class) {
    $html = "<nav class='$class'>\n";
    foreach($items as $key => $item) {
      $selected = (isset($item['url'])) && substr(basename($_SERVER['PHP_SELF']), 0, -4) == $key ? 'selected' : null; 
      $html .= "<a href='{$item['url']}' class='{$selected}'>{$item['text']}</a>\n";
    }
    $html .= "</nav>\n";
    return $html;
  }
}