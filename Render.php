<?php namespace Mreschke\Render;

use Layout;
use Request;
use Mreschke\Dbal\DbalInterface;

/**
 * Render gui components.
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Render
{
    protected $db;

    /**
     * Create a new Render instance.
     * @param DbalInterface $db
     */
    public function __construct(DbalInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Change the DbalInterface connection string by name.
     * @param  string $connection key name
     * @param  DbalInterface $db
     * @return self chainable
     */
    public function connection($connection, DbalInterface $db = null)
    {
        if (isset($db)) {
            $this->db = $db;
        }
        $this->db->connection($connection);
        return $this;
    }

    /**
     * Render a jquery select2 select box
     * http://ivaynberg.github.io/select2/
     * @param  string $name
     * @param  array  $list
     * @param  string|array $selected
     * @param  array  $options
     * @return html echo output
     */
    public function select2($name, $list = array(), $selected = null, $options = array())
    {
        // Default Options
        $options = array_merge([
            'placeholder' => 'Select...',
            'firstBlank' => true,
            'width' => 'resolve',
            'multiple' => false,
            'closeOnSelect' => true,
            'class' => 'select2',
            'useTags' => false,
            'maxTagLength' => 25,
            'tagSeparators' => [",", " "],
            'minimumInputLength' => 0,
            'url' => '',
        ], $options);


        if (!Request::ajax()) {
            $tagText = '';
            if (isset($list) && !$options['useTags'] && !$options['url']) {
                $multipleText = '';
                if ($options['multiple']) {
                    $multipleText = "multiple='multiple'";
                }
                echo "<select id='$name' name='$name' class='$options[class]' width='auto' $multipleText>";
                if ($options['firstBlank']) {
                    echo "<option></option>";
                }
                foreach ($list as $key => $value) {
                    $selectedText = '';
                    if (isset($selected) && isset($key)) {
                        if ($selected == $key) {
                            $selectedText = 'selected';
                        }
                    }
                    if (!$key && !$value) {
                        echo "<option></option>";
                    } else {
                        echo "<option value='$key' $selectedText>$value</option>";
                    }
                }
                echo '</select>';
            } elseif ($options['useTags']) {
                echo "<input type='hidden' id='$name'/>";
                $tagText = 'tags: ["'.implode('","', $list).'"]';
                $tagText .= ", maximumInputLength: $options[maxTagLength]";
                $tagText .= ', tokenSeparators: '.json_encode($options['tagSeparators']);
                $tagText .= ",";
            } elseif ($options['url']) {
                echo "<input type='hidden' id='$name'/>";
            }


            $script = "
                \$('#$name').select2({
                    placeholder: '$options[placeholder]',
                    width: '$options[width]',
                    closeOnSelect: '$options[closeOnSelect]',
                    $tagText
                    minimumInputLength: $options[minimumInputLength]
            ";

            if ($options['url']) {
                $script .= "
                    ,ajax: {
                        url: '$options[url]',
                        dataType: 'jsonp',
                        quietMillis: 250,
                        data: function (term, page) {
                            return {
                                q: term,
                                page_limit: 10
                            };
                        },
                        results: function (data) {
                            //var tuples = [];
                            //for (var key in data) tuples.push([key, data[key]]);
                            /*tuples.sort(function(a,b) {
                                a = a[1];
                                b = b[1];
                                return a < b ? -1 : (a > b ? 1 : 0);
                            });*/
                            //return {results: tuples};
                            /*for (var i = 0; i < tuples.length; i++) {
                                var key = tuples[i][0];
                                var value = tuples[i][1];
                            }*/

                            var tmp = [];
                            $.each(data, function(key, value) {
                                tmp.push({'id': key, 'value': value});
                            });
                            return {results: tmp};

                            //for (var key in data) {
                            //    tmp.push({'id': key, 'value': data[key]});
                            //}
                            //return {results: tmp};
                        }
                    },
                    formatResult: function (data) {
                        //return data[1];
                        return data.value;
                    },
                    formatSelection: function (data) {
                        return data.value;
                    },
                    sortResults: function(results, container, query) {
                        // There are no associative arrays in javascript
                        // and my data is actually an object and objects have no
                        // sort order, so I have to sort the final display
                        if (query.term) {
                            // use the built in javascript sort function
                            return results.sort(function(a, b) {
                                a = a.value;
                                b = b.value;
                                return a < b ? -1 : (a > b ? 1 : 0);
                            });
                        }
                        return results;
                    }
                ";
            }
            $script .= "});";


            // Inject script into mrcore
            Layout::script($script);
        }
    }

    /**
     * Render a jquery datatables element.
     * @param  string $name table id
     * @param  function $function
     * @param  string $ajaxUrl optional ajax url
     * @return html|json
     */
    public function datatables($name, $function, $ajaxUrl = '')
    {
        $dt = new Datatables();
        call_user_func($function, $dt);
        if (Request::ajax()) {

            //FIX ME, if two datatables on same page, get problems

            // Ajax request from datatables script below
            #if ($name == 'tableTwo') {
                return $dt->render($this->db);
            #}
        } else {
            // Initial view, non ajax.  Show dataTables javascript and empty table template
            // We keep the main dataTables.js, which has all the fnSetFilterDelay!!!
            $oTable = studly_case($name);
            Layout::script("
                var search_timeout = undefined;
                $(function() {
                    var $oTable = $('#$name').dataTable({
                        'bProcessing': true,
                        'bServerSide': true,
                        'sAjaxSource': '$ajaxUrl?viewmode=raw&table=$name&key=8feb8b9265a3878fcb204a591dcb91a5',

                        //Default Page Size
                        'iDisplayLength': 20,

                        //Turn off initial sorting, I let my model do the default sort
                        'aaSorting': [],

                        //Set Language and Options for:
                        'oLanguage': {
                            //Text and options of page length dropdown
                            'sLengthMenu': 'Show <select class=\"form-control input-sm\">'+
                                '<option value=10>10</option>'+
                                '<option value=15>15</option>'+
                                '<option value=20>20</option>'+
                                '<option value=25>25</option>'+
                                '<option value=30>30</option>'+
                                '<option value=40>40</option>'+
                                '<option value=50>50</option>'+
                                '<option value=75>75</option>'+
                                '<option value=100>100</option>'+
                                '<option value=-1>All</option>'+
                                '</select> entries'
                        }

                    });
                    //}).fnSetFilteringDelay(); when you fix filter delay

                    $('tfoot input').keyup( function (event) {
                        if(event.keyCode!='9') {
                            if(search_timeout != undefined) {
                                clearTimeout(search_timeout);
                            }
                            \$this = this;
                            search_timeout = setTimeout(function() {
                                search_timeout = undefined;
                                $oTable.fnFilter( \$this.value, $('tfoot input').index(\$this) );
                            }, 400);
                        }
                    });
                })
            ");
            echo $dt->outputTemplate($name);
        }
    }
}
