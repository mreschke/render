<?php namespace Mreschke\Render;

use Mreschke\Dbal\DbalInterface;
use Input;

/**
 * Render jquery datatables element.
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Datatables
{
	private $db;
	private $debug;
	private $columns;
	private $from;
	private $where;
	private $order;
	private $query;
	private $totalQuery;
	private $filteredTotalQuery;
	private $customColumns;
	private $style;
	private $hideFooter;

	/**
	 * Create a new Datatables instance.
	 */
	public function __construct()
	{
		$this->debug = false;
		$this->column = array();
		$this->customColumns = array();
		$this->hideFooter = false;
	}

	/**
	 * Add column to query builder or return columns.
	 * @param  string $display column display name
	 * @param  string $column column name
	 * @param  string $datatype string|int|bool|nullbool
	 * @param  boolean $visible is column visible on grid
	 * @return void|array
	 */
	public function column($display = null, $column = null, $datatype = 'string', $visible = true)
	{
		if (isset($display)) {
			$this->columns[] = array(
				'display' => $display,
				'column' => $column,
				'datatype' => strtolower($datatype),
				'visible' => $visible
			);
		} else {
			return $this->columns;
		}
	}

	/**
	 * Add custom column data or return all custom columns.
	 * @param  string $column column name
	 * @param  string $value custom column attribute
	 * @return void|array
	 */
	public function customColumn($column = null, $value = null) {
		if (isset($column)) {
			$this->customColumns[$column] = $value;
		} else {
			return $this->customColumns;
		}
	}

	/**
	 * Get/Set the style for the entire table.
	 * @param  string $value
	 * @return void|string
	 */
	public function style($value = null) {
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Get/Set option to hide datatables footer.
	 * @param  boolean $value
	 * @return void|boolean
	 */
	public function hideFooter($value = null) {
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Get/Set debug option.
	 * @param  boolean $value
	 * @return void|boolean
	 */
	public function debug($value = null)
	{
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Get/Set query builder from sql statment.
	 * @param  string $value sql from statement
	 * @return void|string
	 */
	public function from($value = null)
	{
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Get/Set query builder where sql statement.
	 * @param  string $value sql where statement
	 * @return void|string
	 */
	public function where($value = null)
	{
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Get/Set query builder order by sql statement.
	 * @param  string $value sql order by statement
	 * @return void|string
	 */
	public function order($value = null)
	{
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Get/Set query string.
	 * @param  string $value
	 * @return void|string
	 */
	public function query($value = null)
	{
		return $this->getSet(__FUNCTION__, $value);
	}

	/**
	 * Universal getter and setter helper.
	 * @param  mixed $key
	 * @param  mixed $value
	 * @return void|mixed
	 */
	private function getSet($key, $value = null) {
		if (isset($value)) {
			$this->$key = $value;
		} else {
			return $this->$key;
		}
	}

	/**
	 * Expand query, execute query, build datatables json return string.
	 * @param  DbalInterface $db
	 * @return string json
	 */
	public function render(DbalInterface $db)
	{
		$this->sql = $db;

		// Use datatables GET variables plus our oritinal SQL query and expand
		// our query to fit all new filters and sorts
		$this->expand();

		// Get total rows
		$total = $db->query($this->totalQuery)->value();

		// Get total filtered rows
		$filteredTotal = $db->query($this->filteredTotalQuery)->value();

		// Get our data
		$result = $db->query($this->query);

		// Return dataTables output response
		echo json_encode(
			$this->output($result, $total, $filteredTotal)
		);

	}

	/**
	 * Build datatables output array for query rows/columns.
	 * @param  Dbal result $result
	 * @param  int $total
	 * @param  int $filteredTotal
	 * @return array
	 */
	private function output($result, $total, $filteredTotal) {
		$rows = $result->getAssoc();

		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $total,
			"iTotalDisplayRecords" => $filteredTotal,
			"aaData" => array()
		);

		if (isset($rows)) { // required
			foreach ($rows as $row) {
			#for ($r = 0; $r <= $result->count() - 1; $r++) {
				#echo "dd";


				#$row = mssql_fetch_assoc($this->result);
				$line = array();
				$f=0;
				foreach($row as $colname => $data) {
					if ($this->columns[$f]['visible']) {
						$datatype = $this->columns[$f]['datatype'];

						//Escape data
						$data = htmlentities($data, ENT_QUOTES, 'utf-8', false);

						// Field Types
						if ($datatype == 'bool') {
							$data = ($data == 1 ? 'True' : 'False');
						}

						// Custom Columns
						foreach ($this->customColumns as $key => $value) {
							if (strtolower($colname) == strtolower($key)) {
								$data = $value;
								if (preg_match_all("'%(.*?)%'", $data, $matches)) {
									foreach ($matches[1] as $variable) {
										$merge = $row[$variable];
										$merge = htmlentities($merge, ENT_QUOTES, 'utf-8', false);
										$data = preg_replace(
											"'%$variable%'",
											$merge,
											$data
										);
									}
								}
							}
						}
						#htmlentities($row[$variable], ENT_QUOTES, 'utf-8', false),

						/*foreach($this->columns as $custcolname => $custcoldata) {
							if (strtolower($custcolname) == strtolower($colname)) {
								$data = $custcoldata;
								foreach ($row as $regexcolname => $regexdata) {
									$data = preg_replace("'%$regexcolname%'", $row[$regexcolname], $data);
								}
								break;
							}
						}
						*/

						$line[] = $data;
					}
					$f++;
				}
				$output['aaData'][] = $line;
			}
		}
		return $output;
	}

	/**
	 * Output the initial databables template or table shell, no data.
	 * @param  string $name
	 * @return string html table
	 */
	public function outputTemplate($name)
	{
		$html = '';
		$html .= "<table id='$name' class='table table-condensed table-striped table-bordered table-hover' ";
		if ($this->style) {
			$html .= "style='".$this->style."' ";
		}
		$html .= ">";
		$html .= "<thead><tr>";
		foreach ($this->column() as $col) {
			if ($col['visible']) {
				$html .= "<th style='width:1px'>".$col['display']."</th>";
			}

		}

		$html .= "</tr></thead><tbody></tbody>";
		if (!$this->hideFooter) {
			$html .= "<tfoot><tr>";
			for ($i = 0; $i < count($this->column()); $i++) {
				if ($this->columns[$i]['visible']) {
					$html .= "<th><input type='text' name='search_$i' class='search_init form-control' style='width: 100%' /></th>";
				}
			}

			$html .= "</tr></tfoot>";
		}
		$html .= "</table>";
		return $html;
	}

	/**
	 * Build our sql query based on datatables json ajax requests.
	 * @return void, builds $this->query instead
	 */
	public function expand()
	{
		// Datatables Query Strings
		$iColumns = intval(Input::get('iColumns')); // Count of visible columns
		$sSortCol = array();
		$sSearch = Input::get('sSearch'); // Master search text
		$sSearchCol = array();
		$iSortingCols = intval(Input::get('iSortingCols'));
		$iSortCol = array();
		$iSortColDir = array();
		$iDisplayStart = Input::get("iDisplayStart");
		$iDisplayLength = Input::get("iDisplayLength");
		$iSortCol0 = Input::get("iSortCol_0");

		//Prepare SQL Where (Column Search)
		//Each column search is $_GET['sSearch_x'] where x is column number, starting at 0
		for ($i=0; $i < $iColumns; $i++) {
			if (Input::get("bSearchable_$i") == "true" && Input::get("sSearch_$i") != '') {
				//Datatables column is searchable, and is being searched
				$sSearchCol[$i] = Input::get("sSearch_$i");
			}
		}

		//Prepare SQL Sorting
		//First sorted column is $_GET['iSortCol_x'] (where x is the first column sorted, starts at 0);
		//$_GET['sSortDir_0']=asc or desc is first column direction
		if (isset($iSortCol0)) {
			for ($i=0; $i < $iSortingCols; $i++) {
				if (Input::get("bSortable_".intval(Input::get("iSortCol_$i"))) == "true") {
					//Datatables column is sortable, and is set to sort
					$iSortCol[] = Input::get("iSortCol_$i");
					$iSortColDir[] = Input::get("sSortDir_$i");
				}
			}
		}

		//Prepare SQL Limits
		//Current page is $_GET['iDisplayStart'] which is page-1 * page size
		//Page length is $_GET['iDisplayLength']
		#if (!$iDisplayStart) $iDisplayStart = null;
		if (!$iDisplayLength) $iDisplayLength = -1;

		//SQL Total Query
		//Here because $this->where has NOT had any filtered added yet
		if ($this->where != '') $this->where = "WHERE ".$this->where;
		$this->totalQuery = "SELECT COUNT(*) FROM ".$this->from." ".$this->where;
		if ($this->debug) echo "totalQuery: ".$this->totalQuery."\r\n\r\n";

		//Global Search Filtering
		#\Helper\Datatables::datatables_global_filter($this->where, $search, $aColumns, $all_cols);
		if ($sSearch != "") {
			$sSearch = strtolower($this->sql->escape($sSearch));
			$this->where .= ($this->where=='') ? "WHERE (" : " AND (";
			for ($i=0; $i < count($this->columns); $i++) {
				#$datatype = $all_cols[$i]->datatype;
				//Only global filter on string or date columns, integers and bools are pointless
				#if (($datatype == 'string' || $datatype == 'date') || $ignore_datatypes) {
					$col = $this->columns[$i]['column'];
					#if (stristr($col, " as ")) $col = substr($col, strripos($col, " as ")+4);
					if (stristr($col, " as "))  $col = substr($col, 0, strripos($col, " as "));
					if ($col == 'null') {
						//Nothing, don't Global Filter on these columns
					} else {
						$this->where .= $col." LIKE '%".$this->sql->escape($sSearch)."%' OR ";
					}
				#}
			}
			$this->where = substr_replace($this->where, "", -3).')';
		}

		//Individual Column Search Filtering
		if (isset($sSearchCol)) {
			foreach ($sSearchCol as $key => $val) {
				$val = strtolower($this->sql->escape($val));
				$c = -1;
				foreach ($this->columns as $column) {
					if ($column['visible']) $c++;
					if ($c == intval($key)) break;
				}
				$col = $column['column'];
				$datatype = $column['datatype'];




				#$col = $this->columns[intval($key)]->column;
				#$datatype = $this->columns[intval($key)]->datatype;

				// Parser && (AND) and || (OR) multiple statements
				$matchor = false;
				$matches = array($val);
				if (preg_match("/\&\&/", $val)) {
					$matches = explode("&&", $val);
				} elseif (preg_match("/\|\|/", $val)) {
					$matchor = true;
					$matches = explode("||", $val);
				}

				foreach ($matches as $val) {
					$val = trim($val);
					if (stristr($col, " as ")) {
						$col_name = substr($col, strripos($col, " as ")+4);
						$col = substr($col, 0, strrpos($col, " as "));
					}
					if ($matchor) {
						$this->where .= ($this->where=='') ? "WHERE " : " OR ";
					} else {
						$this->where .= ($this->where=='') ? "WHERE " : " AND ";
					}

					$yes = array('y','yes','1','e','s','ye','es','t','true');
					$no = array('n','no','o','-1','f','false');
					$na = array('na','n/a','0');

					if ($val == '!') {
						$this->where .= "($col = '' or $col is NULL) ";
					} elseif ($val == '=') {
						$this->where .= "($col <> '' AND $col is NOT NULL) ";
					} else {
						if ($datatype == 'nullbool') {
							//Searching on nullbool (Yes/No/NA)
							#if ($col_name == 'accredited' || $col == 'd.reviewed' || $col == 'd.memo_recorded' || $col == 'd.approved') {
							if (in_array($val, $yes)) {
								$this->where .= "$col = 1 ";
							} elseif (in_array($val, $no)) {
								$this->where .= "$col = -1 ";
							} elseif (in_array($val, $na)) {
								$this->where .= "$col = 0 ";
							} else {
								$this->where .= "$col LIKE '%$val%' ";
							}
						} elseif ($datatype == 'bool') {
							//Searching on bool (Yes/No) Column
							#} elseif ($col == 'd.land_owner' || $col == 'd.land_manager' || $col == 'd.investor_land' || $col == 'd.investor_community' || $col == 'd.tge' || $col == 'd.other') {
							if (in_array($val, $yes)) {
								$this->where .= "$col = 1 ";
							} elseif (in_array($val, $no)) {
								$this->where .= "$col = 0 ";
							} else {
								$this->where .= "$col LIKE '%$val%' ";
							}
						} else {
							if (in_array(substr($val, 0, 2), array('<=', '>=', '!='))) {
								$condit = substr($val, 0, 2);
								$val = substr($val, 2);
								if ($datatype == 'string' || $datatype == 'date') $val = "'$val'";
							} elseif (in_array(substr($val, 0, 1), array('<', '>', '='))) {
								$condit = substr($val, 0, 1);
								$val = substr($val, 1);
								if ($datatype == 'string' || $datatype == 'date') $val = "'$val'";
							} else {
								if (substr($val, 0, 1) == '!') {
									$condit = 'NOT LIKE';
									$val = "'%".substr($val, 1)."%'";
								} else {
									$condit = 'LIKE';
									$val = "'%$val%'";
								}

							}
							$this->where .= "$col $condit $val ";
						}
					}
				}
			}
		}

		//Ordering
		if (count($iSortCol) > 0) {
			$this->order = "ORDER BY  ";
			for ($i=0 ; $i < count($iSortCol); $i++) {
				$col = $this->columns[$iSortCol[$i]]['column'];
				if (stristr($col, " as ")) $col = substr($col, strripos($col, " as ")+4);
				$this->order .= $col." ".$this->sql->escape($iSortColDir[$i]) .", ";
			}
			$this->order = substr_replace($this->order, "", -2);
			if ($this->order == "ORDER BY") $this->order = "";
		} else {
			if ($this->order != '') $this->order = "ORDER BY ".$this->order;
		}

		//Paging (Limit)
		$limit = '';
		if (isset($iDisplayStart) && $iDisplayLength != '-1') {
			#if ($this->db_type == 'mysql') {
			#	$limit = "LIMIT ".$this->sql->escape($iDisplayStart).", ".$this->sql->escape($iDisplayLength);
			#} elseif ($this->db_type == 'mssql') {
				//FIXME, MSSQL paging!
				// NOTE, offset requires an order by, so datatables render requires $dt->order()
				$limit = "OFFSET ".$iDisplayStart." ROWS FETCH NEXT ".$iDisplayLength." ROWS ONLY;";
				//OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY;
			#}
		}

		//SQL Query Builder
		$cols = array(); foreach ($this->columns as $col) $cols[] = $col['column'];
		$this->query = "
			SELECT ".str_replace(" , ", " ", implode(", ", $cols))." FROM ".$this->from." ".$this->where." ".$this->order." ".$limit;
		if ($this->debug) echo "Query: ".$this->query."\r\n\r\n";

		//SQL Total Filtered Query
		//Here because $this->where has had all filtered added
		$this->filteredTotalQuery = "SELECT COUNT(*) FROM ".$this->from." ".$this->where;
		if ($this->debug) echo "filteredTotalQuery: ".$this->filteredTotalQuery."\r\n\r\n";
	}

}
