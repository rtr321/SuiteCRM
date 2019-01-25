<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */

/**
 * SugarHtml is a static class that provides a collection of helper methods for creating HTML DOM elements.
 *
 * @auther Justin Park(jpark@sugarcrm.com)
 *
 */
class SugarHtml
{
    const SINGLE_QUOTE = "'";
    const DOUBLE_QUOTE = '"';
    const ASSIGN_SIGN = "=";
    const HTML_TAG_BEGIN = "<";
    const HTML_TAG_END = ">";
    const SMARTY_TAG_BEGIN = "{";
    const SMARTY_TAG_END = "}";
    const CLAUSE_TAG_BEGIN = "(";
    const CLAUSE_TAG_END = ")";

    /**
     * @var integer the counter for generating automatic input field names.
     */
    public static $count=0;

    /**
     * @static
     * Create an open html element
     *
     * @param String $tagName - the tag name
     * @param array $params - the element attributes
     * @param bool $self_closing - whether the element is self-closing or not
     * @return string - generated html string
     */
    public static function createOpenTag($tagName, $params = array(), $self_closing = false)
    {
        $self_closing_tag = ($self_closing) ? "/" : "";
        if (empty($params)) {
            return "<{$tagName}{$self_closing_tag}>";
        }

        $options = self::createAttributes($params);
        return "<{$tagName} {$options}{$self_closing_tag}>";
    }
    /**
     * @static
     * Create a close html element
     *
     * @param String $tagName - the tag name
     * @return string - generated html string
     */
    public static function createCloseTag($tagName)
    {
        return "</{$tagName}>";
    }
    /**
     * @static
     * Create a html block given a cascade array tree.
     * Resemble the array form generated by parseHtmlTag
     * @see SugarHtml::parseHtmlTag
     *
     * @param array $dom_tree - Cascade array form
     * <pre>
     * 1. Simple html format
     * array(
     *      'tag' => 'div',
     *      // all attributes are assigned in key-value form
     *      'id' => 'div_id',
     *      'class' => 'wrapper',
     *      ...
     * )
     *
     * 2. Cascade html format
     * array(
     *      'tag' => 'div',
     *      //all subitems are assigned in container
     *      'container' => array(
     *          array(
     *              'tag' => 'input',
     *              'type' => 'hidden',
     *          )
     *      )
     * )
     *
     * 3. Siblings
     * array(
     *      //all siblings are assigned in a parallel array form
     *      array('tag' => 'input', 'type' => 'button', ... ),
     *      array('tag' => 'input', 'type' => 'submit', ... )
     * )
     *
     * 4. Smarty
     * array(
     *      'smarty' => array(
     *          array(
     *              //smarty must contain 'template'. Container will replace with the same key value
     *              'template' => '{if}[CONTENT1]{else}[CONTENT2]{/if}',
     *              //content1 will be assign on the lefthand side, and content2 will be on righthand side
     *              '[CONTENT1]' => array(
     *                  //cascade valid array form
     *              )
     *              '[CONTENT2]' => array(...)
     *          ),
     *      )
     * )
     * </pre>
     *
     * @return string - generated html string
     */
    public static function createHtml($dom_tree = array())
    {
        $out = "";
        if (isset($dom_tree[0])) { //dom contains sibling items
            foreach ($dom_tree as $dom) {
                $out .= is_array($dom) ? self::createHtml($dom) : $dom;
            }
        } else {
            if (isset($dom_tree['tag'])) {
                $tagName = $dom_tree['tag'];
                $self_closing = $dom_tree['self_closing'];
                unset($dom_tree['tag']);
                unset($dom_tree['self_closing']);
                if (isset($dom_tree['container'])) {
                    $container = $dom_tree['container'];
                    unset($dom_tree['container']);
                }
                $out .= self::HTML_TAG_BEGIN."{$tagName} ";
                if (isset($dom_tree['smarty'])) {
                    $out .= self::createHtml(array(
                    'smarty' => $dom_tree['smarty']
                )).' ';
                    unset($dom_tree['smarty']);
                }
                $out .= self::createHtml($dom_tree);
                if ($self_closing) {
                    $out .= '/>';
                } else {
                    $out .= self::HTML_TAG_END;
                    $out .= (is_array($container)) ? self::createHtml($container) : $container;
                    $out .= self::createCloseTag($tagName);
                }
            } else {
                if (isset($dom_tree['smarty'])) { //dom contains smarty function
                    $count = 0;
                    foreach ($dom_tree['smarty'] as $blocks) {
                        $template = $blocks['template'];
                        unset($blocks['template']);
                        $replacement = array();
                        foreach ($blocks as $key => $value) {
                            $replacement[$key] = is_array($value) ? self::createHtml($value) : $value;
                        }
                        if ($count++ > 0) {
                            $out .= ' ';
                        }
                        $out .= strtr($template, $replacement);
                    }
                } else {
                    $count = 0;
                    foreach ($dom_tree as $attr => $value) {
                        if ($count++ > 0) {
                            $out .= ' ';
                        }
                        $out .= (empty($value)) ? $attr : $attr.'="'.$value.'"';
                    }
                }
            }
        }

        return $out;
    }

    public static function parseSugarHtml($sugar_html = array())
    {
        $output = array();
        $input_types = array(
            'submit', 'button', 'hidden', 'checkbox', 'input'
        );

        if (in_array($sugar_html['type'], $input_types)) {
            $sugar_html['htmlOptions']['type'] = (empty($sugar_html['htmlOptions']['type'])) ? $sugar_html['type'] : $sugar_html['htmlOptions']['type'];
            $sugar_html['htmlOptions']['value'] = $sugar_html['value'];

            $output = array_merge(array(
                'tag' => 'input',
                'self_closing' => true,
            ), $sugar_html['htmlOptions']);
        }
        if (isset($sugar_html['template'])) {
            $output = array(
                'smarty' => array(
                    array(
                        'template' => $sugar_html['template'],
                        '[CONTENT]' => $output
                    )
                )
            );
        }

        return $output;
    }

    /**
     * @static
     * Disassemble an html string into a cascaded array form elements
     *
     * @param String $code - html string (support the mixed html string with smarty blocks)
     * @param array $appendTo - Precedent siblings
     * @return array - structual array form, can be restored in a html code by createHtml
     * @see SugarHtml::createHtml
     */
    public static function parseHtmlTag($code, $appendTo = array())
    {
        $code = ltrim($code);
        $start_pos = strpos($code, ' ') ? strpos($code, ' ') : strpos($code, self::HTML_TAG_END);
        $output = array();
        if (substr($code, 0, 1) != self::HTML_TAG_BEGIN || $start_pos === false) {
            $offset = 0;
            self::parseSmartyTag($code, $output, $offset);
            $remainder = ltrim(substr($code, $offset));
            if (!empty($remainder)) {
                array_push($appendTo, $output);
                return self::parseHtmlTag($remainder, $appendTo);
            }
        } else {
            $tag = substr($code, 1, $start_pos - 1);
            $closing_tag = '</'.$tag;
            $end_pos = strpos($code, $closing_tag, $start_pos + 1);
            $output['tag'] = $tag;

            if ($end_pos === false) {
                $output['self_closing'] = true;
                $code = substr($code, $start_pos + 1);
            } else {
                $output['self_closing'] = false;
                $code = substr($code, $start_pos + 1, $end_pos - $start_pos - 1);
            }
            $remainder = self::extractAttributes($code, $output);

            if (!empty($remainder)) {
                array_push($appendTo, $output);
                return self::parseHtmlTag($remainder, $appendTo);
            }
        }
        return (empty($appendTo)) ? $output : array_merge($appendTo, array($output));
    }

    /**
     * @static
     * Disassemble a smarty string into a cascaded array form elements
     *
     * @param String $code - smarty encoded string
     * @param Array $output - parsed attribute
     * @param int $offset
     * @param bool $is_attr - whether current smarty block is inside html attributes or not
     */
    public static function parseSmartyTag($code, &$output, &$offset = 0, $is_attr = false)
    {
        if (empty($output['smarty'])) {
            $output['smarty'] = array();
        }

        $_str = ltrim(substr($code, $offset + 1));

        preg_match("/^[$\w]+/", $_str, $statement);
        $_smarty_closing = self::SMARTY_TAG_BEGIN.'/'.$statement[0];
        $_left = strlen($statement[0]);

        $_right = strpos($code, $_smarty_closing, $offset);
        if ($_right === false) { //smarty closed itself
            $_right = strpos($code, self::SMARTY_TAG_END, $offset);
        } else {
            preg_match_all('/\{( |)+'.substr($_str, 0, $_left).'/', substr($_str, 0, $_right), $matches);

            $match_count = count($matches[0]);
            while ($match_count-- > 0) {
                $_right = strpos($code, $_smarty_closing, $_right + strlen($_smarty_closing));
            }

            $_right = strpos($code, self::SMARTY_TAG_END, $_right);
        }
        $smarty_string = substr($code, $offset, $_right + 1 - $offset);

        $clauses = array_slice(
            //split into each clause
            preg_split("/[\{\}]/i", $smarty_string),
            1, -1 //slice out the first and last items, which is empty.
        );
        $smarty_template = array(
            'template' => '',
        );
        //Concatenate smarty variables
        $reserved_strings = array(
            '$', 'ldelim', 'rdelim'
        );
        $reserved_functions = array(
            'literal' => false,
            'nocache' => false,
        );
        $queue = 0;
        $is_literal = false;
        $current_literal_string = '';
        for ($seq = 0; $seq < count($clauses); $seq++) {
            $is_reserved = false;

            $current_literal_string = !empty($current_literal_string) ? $current_literal_string : (isset($reserved_functions[trim($clauses[$seq])]) ? trim($clauses[$seq]) : '');
            $is_literal = $is_literal || !empty($current_literal_string);

            foreach ($reserved_strings as $str) {
                if (substr(ltrim($clauses[$seq]), 0, strlen($str)) == $str) {
                    $is_reserved = true;
                    break;
                }
            }
            if ($is_literal || ($seq > 0 && $is_reserved)) {
                if ($queue == 0) {
                    $clauses[$queue] = self::SMARTY_TAG_BEGIN.$clauses[$seq].self::SMARTY_TAG_END;
                } else {
                    $clauses[--$queue] .= self::SMARTY_TAG_BEGIN.$clauses[$seq].self::SMARTY_TAG_END;
                }
                $is_literal = $is_literal && (substr(ltrim($clauses[$seq]), 0, strlen("/".$current_literal_string)) != "/".$current_literal_string);
                $current_literal_string = ($is_literal) ? $current_literal_string : '';
                if ($seq < count($clauses) - 1) {
                    $clauses[$queue++] .= $clauses[++$seq];
                } else {
                    $queue++;
                }
            } else {
                $clauses[$queue++] = $clauses[$seq];
            }
        }
        array_splice($clauses, $queue);
        //Split phrases for the conditional statement
        $count = 0;
        $queue = 0;
        for ($seq = 0; $seq < count($clauses); $seq++) {
            if ($seq > 0 && substr(ltrim($clauses[$seq]), 0, 2) == 'if') {
                $count++;
            }
            if ($count > 0) {
                $clauses[--$queue] .= ($seq % 2 == 0) ? self::SMARTY_TAG_BEGIN.$clauses[$seq].self::SMARTY_TAG_END : $clauses[$seq];
                if ($seq < count($clauses)) {
                    $clauses[$queue++] .= $clauses[++$seq];
                }
            } else {
                $clauses[$queue++] = $clauses[$seq];
            }

            if ($seq > 0 && substr(ltrim($clauses[$seq - 1]), 0, 3) == '/if') {
                $count--;
            }
        }
        array_splice($clauses, $queue);

        //resemble the smarty phases
        $seq = 0;
        foreach ($clauses as $index => $clause) {
            if ($index % 2 == 0) {
                if (self::SMARTY_TAG_BEGIN == substr($clause, 0, 1) && self::SMARTY_TAG_END == substr($clause, -1, 1)) {
                    $smarty_template['template'] .= $clause;
                } else {
                    $smarty_template['template'] .= '{'.$clause.'}';
                }
            } else {
                if (!empty($clause)) {
                    $key = '[CONTENT'.($seq++).']';
                    $smarty_template['template'] .= $key;
                    $params = array();
                    if ($is_attr) {
                        self::extractAttributes($clause, $params);
                    } else {
                        $params = self::parseHtmlTag($clause);
                    }
                    $smarty_template[$key] = $params;
                }
            }
        }
        $output['smarty'][] = $smarty_template;
        $offset = $_right + 1;
    }

    /**
     * @static
     * Disassemble an html attributes into a key-value array form
     *
     * @param String $code - attribute string (i.e. - id='form_id' name='button' value='View Detail' ...)
     * @param Array $output - Parsed the attribute into key-value form
     * @return string - Remainder string by spliting with ">"
     */
    public static function extractAttributes($code, &$output)
    {
        $var_assign = false;
        $quote_encoded = false;
        $smarty_encoded = false;
        $cache = array();
        $code = rtrim($code);
        for ($i = 0; $i < strlen($code) ; $i ++) {
            $char = $code[$i];
            if (!$smarty_encoded && ($char == self::SINGLE_QUOTE || $char == self::DOUBLE_QUOTE)) {
                if (empty($quote_type)) {
                    $quote_encoded = true;
                    $quote_type = $char;
                } else {
                    if ($quote_type == $char) {
                        if (!empty($cache)) {
                            $string = implode('', $cache);
                            if (empty($var_name)) {
                                $var_name = $string;
                            } else {
                                if ($var_assign) {
                                    $output[trim($var_name)] = $string;
                                    unset($var_name);
                                }
                            }
                        }
                        $quote_type = '';
                        $var_assign = false;
                        $cache = array();
                        $quote_encoded = false;
                    } else {
                        array_push($cache, $char);
                    }
                }
            } else {
                if ($quote_encoded && $char == self::SMARTY_TAG_BEGIN) {
                    $smarty_encoded = true;
                    array_push($cache, $char);
                } else {
                    if ($quote_encoded && $char == self::SMARTY_TAG_END) {
                        $smarty_encoded = false;
                        array_push($cache, $char);
                    } else {
                        if (!$quote_encoded && $char == ' ') {
                            if (!empty($cache)) {
                                $string = implode('', $cache);
                                if (empty($var_name)) {
                                    $var_name = $string;
                                } else {
                                    if ($var_assign) {
                                        $output[trim($var_name)] = $string;
                                        unset($var_name);
                                    }
                                }
                                $quote_encoded = false;
                                $var_assign = false;
                                $cache = array();
                            }
                        } else {
                            if (!$quote_encoded && $char == self::ASSIGN_SIGN) {
                                if (!empty($var_name)) {
                                    $output[$var_name] = '';
                                }
                                $string = implode('', $cache);
                                if (trim($string) != "") {
                                    $var_name = $string;
                                }
                                $var_assign = true;
                                $cache = array();
                            } else {
                                if (!$quote_encoded && $char == self::SMARTY_TAG_BEGIN) {
                                    self::parseSmartyTag($code, $output, $i, true);
                                } else {
                                    if (!$quote_encoded && $char == self::HTML_TAG_END) {
                                        break;
                                    } else {
                                        array_push($cache, $char);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($cache)) {
            $var_name = implode('', $cache);
            $output[$var_name] = '';
        }

        if (isset($output['self_closing']) && $output['self_closing'] === false) {
            $output['container'] = self::parseHtmlTag(substr($code, $i + 1));
            return '';
        }

        return substr($code, $i + 1);
    }

    /**
     * @static
     * Creates HTML attribute elements corresponding key-value pair.
     *
     * @param Array $params - Attributes (key-value pair)
     * @return string - Generated html attribute string
     */
    public static function createAttributes($params)
    {
        $options = "";
        foreach ($params as $attr => $value) {
            if (is_numeric($attr) === false) {
                $attr = trim($attr);
                if ($value) {
                    $options .= $attr.'="'.$value.'" ';
                } elseif (!empty($attr)) {
                    $options .= $attr.' ';
                }
            }
        }
        return $options;
    }
}