<?php 
add_action('admin_menu', 'import_hc_menu');

function import_hc_menu() {
	add_submenu_page( 'tools.php', 'Import Hypercomments', 'Import HC', 'manage_options', 'import-hc-options', 'import_hc_menu_callback' ); 

}


function parse_comments($all_comments) {
	$all_array_comments = array();
	foreach ($all_comments["post"] as $posts_comments) {
		$postid = url_to_postid( $posts_comments["xid"]);
		foreach ($posts_comments["comments"] as $comment) {
			if ($comment[0] == 0){
				$data = array(
					    'comment_post_ID' => $postid,
					    'comment_author' => $comment["nick"],
					    'comment_author_email' => null,
					    'comment_author_url' => null,
					    'comment_content' => $comment["text"],
					    'comment_type' => null,
					    'comment_parent' => 0,
					    'user_id' => null,
					    'comment_author_IP' => $comment["ip"],
					    'comment_agent' => null,
					    'comment_date' => date('Y-m-d H:i:s', strtotime($value_comment["time"])),
					    'comment_approved' => 1,
						);
				$all_array_comments[] = $data;
			} else {
				foreach ($comment as $value_comment) {
					$data = array(
					    'comment_post_ID' => $postid,
					    'comment_author' => $value_comment["nick"],
					    'comment_author_email' => null,
					    'comment_author_url' => null,
					    'comment_content' => $value_comment["text"],
					    'comment_type' => null,
					    'comment_parent' => 0,
					    'user_id' => null,
					    'comment_author_IP' => $value_comment["ip"],
					    'comment_agent' => null,
					    'comment_date' => date('Y-m-d H:i:s', strtotime($value_comment["time"])),
					    'comment_approved' => 1,
						);
				$all_array_comments[] = $data;	
				}
			}
		}
	}
	foreach ($all_array_comments as $value) {
		wp_insert_comment($value);
	}
}

add_action('wp_ajax_import_hc_ajax', 'import_hc_ajax_callback');
function import_hc_ajax_callback() {
	$params =  $_POST['xml_params'];
	$filename = get_attached_file($params["id"]);
	if (file_exists($filename)) {
    $xml = simplexml_load_file($filename);
    $arr = json_decode( json_encode($xml) , 1);
   	parse_comments($arr);
	} else {
	    exit('Не удалось открыть файл');
	}
    wp_die();
}

function import_hc_menu_callback() {
?>
<script>
function run_import_hc() {
	var formData = new FormData();
  formData.append("action", "upload-attachment");
	
  var fileInputElement = document.getElementById("xml-file-import");
  formData.append("async-upload", fileInputElement.files[0]);
  formData.append("name", fileInputElement.files[0].name);
  	
  //also available on page from _wpPluploadSettings.defaults.multipart_params._wpnonce
  <?php $my_nonce = wp_create_nonce('media-form'); ?>
  formData.append("_wpnonce", "<?php echo $my_nonce; ?>");
  var xhr = new XMLHttpRequest();
  xhr.onreadystatechange=function(){
    if (xhr.readyState==4 && xhr.status==200){
    	 var res = JSON.parse(xhr.responseText);
    	 var file_url = res.data["url"];
      jQuery("#button_run_import_hc").prop("disabled", true);
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			action: "import_hc_ajax",
			xml_params: res.data
		},
		success: function(result) {
			if (result != '-1') {
				console.log(result);
				jQuery("#button_run_import_hc").prop("disabled", false);
			}
		},
		error: function(request, status, error) {
			console.log(request.status);
		},
		complete: function() {
			jQuery("#tool-tip").html("Complete!");
		}
	});
    }
  }
  xhr.open("POST","/wp-admin/async-upload.php",true);
  xhr.send(formData);

	
}
		</script>
<div class="wrap">
<h2>Options</h2>
<form method="post" action enctype="multipart/form-data">
<?php wp_nonce_field('update-options'); ?>
<table class="form-table">
	<tr valign="top">
		<th scope="row">Xml from hypercomments</th>
		<td><input id="xml-file-import" type="file"/></td>
	</tr>
</table>
<input type="button" onclick="javascript:run_import_hc();" class="button-primary" name="button_run_import_hc" id="button_run_import_hc" value="<?php _e('Run') ?>" /><p id="tool-tip"></p>
</form>
</div>
<?php } ?>