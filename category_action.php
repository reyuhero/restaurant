<?php

//category_action.php

include('rms.php');

$object = new rms();
if(isset($_GET["action"]) && $_GET["action"] == "all") {
	$output = [];
	$order_query = " ORDER BY category_id ASC ";
	$main_query = "SELECT * FROM product_category_table ";
	
	$object->query = $main_query . $order_query;
	$object->execute();
	$result = $object->get_result();
	foreach ($result as $row) {
		$sid = $row["subcategory"];
		$object->query = "SELECT * FROM product_category_table WHERE category_id = $sid ";
		$subcategory_result = $object->get_result();
		$subcategory_data = [];
		foreach ($subcategory_result as $s) {
			$subcategory_data[] = $s['category_name'];
		}
		$sub_array['subcategory'] = reset($subcategory_data);
		$sub_array['id'] = $row["category_id"];
		$sub_array['name'] = html_entity_decode($row["category_name"]);
		$data[] = $sub_array;
	}
	echo json_encode($data);
}
if (isset($_GET["action"]) && $_GET["action"] == 'categories') {
	$order_column = array('category_name', 'category_status');
	$output = array();
	$main_query = "SELECT * FROM product_category_table ";
	$object->query = $main_query;
	$object->execute();
	if (isset($_GET["order"])) {
		$order_query = ' ORDER BY ' . $order_column[$_GET['order']['0']['column']] . ' ' . $_GET['order']['0']['dir'] . ' ';
	} else {
		$order_query = " ORDER BY category_id ASC ";
	}
	$limit_query = '';
	$total_rows = '';
	$total_pages = '';

	// pagination
	if (isset($_GET["page"]) && isset($_GET["skip"])) {
		$page = 1;
		if (isset($_GET['page'])) {
			$page = filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT);
		}
		$skip = (int)$_GET["skip"] ?? 10;
		$per_page = $skip;
		$total_rows = $object->row_count();
		$total_pages = ceil($total_rows / $per_page);
		$offset = ($page - 1) * $per_page;
		$total_pages = ceil($total_rows / $per_page);
		$limit_query = " LIMIT  $offset , $per_page ";
	}

	$object->query = $main_query . $order_query . $limit_query;
	$object->execute();
	$filtered_rows = $object->row_count();
	$result = $object->get_result();
	$object->query = $main_query;
	$object->execute();
	$data = array();
	foreach ($result as $row) {
		$sub_array = array();
		$sub_array['category_id'] = $row["category_id"];
		$sub_array['category_name'] = html_entity_decode($row["category_name"]);
		$sub_array['category_status'] = $row["category_status"];
		$data[] = $sub_array;
	}
	$output = array(
		"total_records"  	=>  $total_rows,
		"total_page" => $total_pages,
		"recordsFiltered" 	=> 	$filtered_rows,
		"data"    			=> 	$data
	);

	echo json_encode($output);
}

if (isset($_POST["action"])) {

	// get categories
	if ($_POST["action"] == 'fetch') {
		$order_column = array('category_name', 'category_status');
		$output = array();
		$main_query = "SELECT * FROM product_category_table ";
		$search_query = '';
		if (isset($_POST["search"]["value"])) {
			$search_query .= 'WHERE category_name LIKE "%' . $_POST["search"]["value"] . '%" ';
			$search_query .= 'OR category_status LIKE "%' . $_POST["search"]["value"] . '%" ';
		}

		if (isset($_POST["order"])) {
			$order_query = 'ORDER BY ' . $order_column[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
		} else {
			$order_query = 'ORDER BY category_id DESC ';
		}
		$limit_query = '';
		if (isset($_POST["length"])) {
			$limit_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}
		$object->query = $main_query . $search_query . $order_query;
		$object->execute();
		$filtered_rows = $object->row_count();
		$object->query .= $limit_query;
		$result = $object->get_result();
		$object->query = $main_query;
		$object->execute();
		$total_rows = $object->row_count();
		$data = array();

		foreach ($result as $row) {
			$sub_array = array();
			$sub_array[] = html_entity_decode($row["category_name"]);
			$sub = $row["subcategory"];
			$object->query = "SELECT * from product_category_table WHERE category_id = $sub ";
			$subcategory_result = $object->get_result();
			$subcategory_data = [];
			foreach ($subcategory_result as $s) {
				$subcategory_data[] = $s['category_name'];
			}
			$sub_array[] = reset($subcategory_data) ? reset($subcategory_data) : 0;
			$status = '';
			if ($row["category_status"] == 'Enable') {
				$status = '<button type="button" name="status_button" class="btn btn-black btn-sm status_button" data-id="' . $row["category_id"] . '" data-status="' . $row["category_status"] . '">Enable</button>';
			} else {
				$status = '<button type="button" name="status_button" class="btn btn-danger btn-sm status_button" data-id="' . $row["category_id"] . '" data-status="' . $row["category_status"] . '">Disable</button>';
			}
			$sub_array[] = $status;
			$sub_array[] = '
			<div align="center">
			<button type="button" name="edit_button" class="btn btn-warning btn-circle btn-sm categorylist edit_button" data-id="' . $row["category_id"] . '"><i class="fas fa-edit"></i></button>
			&nbsp;
			<button type="button" name="delete_button" class="btn btn-danger btn-circle btn-sm delete_button" data-id="' . $row["category_id"] . '" data-status="' . $row["category_status"] . '"><i class="fas fa-times"></i></button>
			</div>
			';
			$data[] = $sub_array;
		}

		$output = array(
			"draw"    			=> 	intval($_POST["draw"]),
			"recordsTotal"  	=>  $total_rows,
			"recordsFiltered" 	=> 	$filtered_rows,
			"data"    			=> 	$data
		);

		echo json_encode($output);
	}
	// add category
	if ($_POST["action"] == 'Add') {
		$error = '';

		$success = '';

		$data = array(
			':category_name'			=> $_POST["category_name"],
		);

		$object->query = "SELECT * FROM product_category_table WHERE category_name = :category_name";

		$object->execute($data);

		if ($object->row_count() > 0) {
			$error = '<div class="alert alert-danger">Category Already Exists</div>';
		} else {
			$data = array(
				':category_name'			=>	$object->clean_input($_POST["category_name"]),
				':subcategory'			=>	$object->clean_input($_POST["subcategory"]),
				':category_status'			=>	'Enable',
			);
			$object->query = "INSERT INTO product_category_table 
												(category_name, category_status, subcategory) 
												VALUES (:category_name, :category_status, :subcategory)";
			$object->execute($data);
			$success = '<div class="alert alert-success">Category Added</div>';
		}

		$output = array(
			'error'		=>	$error,
			'success'	=>	$success
		);

		echo json_encode($output);
	}
	// category get by id;
	if ($_POST["action"] == 'fetch_single') {
		$id = $_POST["category_id"];
		$object->query = "SELECT * FROM product_category_table WHERE category_id = '$id'";
		$result = $object->get_result();
		$data = array();

		foreach ($result as $row) {
			$sub = $row["subcategory"];
			$object->query = "SELECT * from product_category_table WHERE category_id = $sub ";
			$subcategory_result = $object->get_result();
			$subcategory_data = [];
			foreach ($subcategory_result as $s) {
				$subcategory_data[] = $s['category_name'];
			}
			$sub_array['subcategory_name'] = reset($subcategory_data) ? reset($subcategory_data) : 0;
			$data['category_name'] = $row['category_name'];
			$data['category_name'] = $row['category_name'];
			$data['subcategory'] = $row['subcategory'];
		}

		echo json_encode($data);
	}
	// ----- update category
	if ($_POST["action"] == 'Edit') {
		$error = '';
		$success = '';
		$data = array(
			':category_name'	=>	$_POST["category_name"],
			':category_id'	=>	$_POST['hidden_id']
		);

		$object->query = "SELECT * FROM product_category_table 
		WHERE category_name = :category_name 
		AND category_id != :category_id";

		$object->execute($data);

		if ($object->row_count() > 0) {
			$error = '<div class="alert alert-danger">Category Already Exists</div>';
		} else {
			$data = array(
				':category_name'		=>	$object->clean_input($_POST["category_name"]),
				':subcategory'	=>	$_POST['subcategory']
			);
			$object->query = "UPDATE product_category_table 
												SET category_name = :category_name, subcategory = :subcategory
												WHERE category_id = '" . $_POST['hidden_id'] . "'";
			$object->execute($data);
			$success = '<div class="alert alert-success">Category Updated</div>';
		}

		$output = array(
			'error'		=>	$error,
			'success'	=>	$success
		);

		echo json_encode($output);
	}
	// change category status
	if ($_POST["action"] == 'change_status') {
		$data = array(
			':category_status'		=>	$_POST['next_status']
		);

		$object->query = "UPDATE product_category_table 
											SET category_status = :category_status 
											WHERE category_id = '" . $_POST["id"] . "'";

		$object->execute($data);

		echo '<div class="alert alert-success">Category Status change to ' . $_POST['next_status'] . '</div>';
	}
	// delete category
	if ($_POST["action"] == 'delete') {
		$object->query = "DELETE FROM product_category_table 
										WHERE category_id = '" . $_POST["id"] . "'";
		$object->execute();

		echo '<div class="alert alert-success">Category Deleted</div>';
	}
}
