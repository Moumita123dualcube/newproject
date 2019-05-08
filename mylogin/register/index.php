<?php

require_once('../../config.php');

// Page setup
$PAGE->set_url('/mylogin/register/index.php');
$context = context_system::instance();
$PAGE->set_context($context);
// Don't show blocks
$PAGE->set_pagelayout('standard');
// Add an appropriate body class

$flag = false;
$exceeded = false;
$next = false;
$email_exists = false;
$email_exists_message = false;
$cohort_user = false;

function random_password( $length = 8 ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $password = substr( str_shuffle( $chars ), 0, $length );
    return $password;
}


if(isset($_POST["code"]) && isset($_POST["next"]) && isset($_POST["email"])) {
	$checkCode = $DB->get_record('block_enrol_code', array('code' => $_POST["code"]));

	if(!$checkCode) {
		$flag = true;
	}
	if($checkCode && $checkCode->codesused >= $checkCode->numcodes) {
		$exceeded = true;
	} 

	$checkUser = $DB->get_record('user', array('email' => $_POST["email"]));
	//print_r($checkUser->id);
	//die;
	if($checkUser){
	$cohort_user = $DB->get_record('cohort_members', array('userid' => $checkUser->id));
	}
	// print_r($checkUser);
	// die;
	// print_r($cohort_user);
	// die;
	if($checkUser && $cohort_user) {
		//$email_exists = true;
		$email_exists_message = true;
	}
	if($checkUser && !$cohort_user){
	$user_info = $DB->get_record('user_info_data', array('userid' => $checkUser->id));
	// print_r($user_info);
	// die;
		$pass = random_password(8);
		foreach(json_decode($checkCode->profile_field, true) as $key => $val) {

			$record1 = new stdClass();
			$record1->id = $user_info->id;
			$record1->userid = $checkUser->id;
			$record1->fieldid = $DB->get_record('user_info_field', array('shortname' => $key))->id;
			$record1->data = $val;
			$DB->update_record('user_info_data', $record1);
		}
		
		$cohort = new stdClass();
		$cohort->cohortid = $checkCode->cohortid;
		$cohort->userid = $checkUser->id;
		$cohort->timeadded = time();
		$DB->insert_record('cohort_members', $cohort);
		
		$updateCode = new stdClass();
		$updateCode->id = $checkCode->id;
		$updateCode->codesused = $checkCode->codesused+1;
		$DB->update_record('block_enrol_code', $updateCode);
		
		$user = $DB->get_record('user', array('id' => $checkUser->id));
		$a = new stdClass();
		$a->firstname = $_POST["email"];
		$a->username = $_POST["email"];
		$a->password = $pass;
		$a->link = html_writer::tag('a', $CFG->wwwroot.'/login/index.php', array('href' => $CFG->wwwroot.'/login/index.php'));
		$subject = get_string('credentials_email_subject', 'block_enrol_code');
		$plaintext_body = get_string('credentials_email_body', 'block_enrol_code', $a);
    // Html version
    	$html_body = nl2br(get_string('credentials_email_body', 'block_enrol_code', $a));
    	if(email_to_user($user, 'noreply@els.prepworks2400.com', $subject, $plaintext_body, $html_body)){

    	$email_exists = true;
    	}
	}
	
	if(!$exceeded && !$flag && !$email_exists && !$email_exists_message) {
		$next = true;
	} 
	
} 

if(isset($_POST["code"]) && isset($_POST["submit"])) {
	$checkUser = $DB->get_record('user', array('email' => $_POST["email"]));
	if($checkUser) {
		$email_exists = true;
	} else {
		$pass = random_password(8);
		$checkCode = $DB->get_record('block_enrol_code', array('code' => $_POST["code"]));
		$record = new stdClass();
		$record->confirmed = 1;
		$record->mnethostid = 1;
		$record->username = $_POST["email"];
		$record->password = md5($pass);
		$record->firstname = $_POST["firstname"];
		$record->lastname = $_POST["lastname"];
		$record->email = $_POST["email"];
		$record->city = $checkCode->city;
		$record->country = $checkCode->country;
		$insert = $DB->insert_record('user', $record, true);
		
		foreach(json_decode($checkCode->profile_field, true) as $key => $val) {
			$record1 = new stdClass();
			$record1->userid = $insert;
			$record1->fieldid = $DB->get_record('user_info_field', array('shortname' => $key))->id;
			$record1->data = $val;
			$DB->insert_record('user_info_data', $record1);
		}
		
		$cohort = new stdClass();
		$cohort->cohortid = $checkCode->cohortid;
		$cohort->userid = $insert;
		$cohort->timeadded = time();
		$DB->insert_record('cohort_members', $cohort);
		
		$updateCode = new stdClass();
		$updateCode->id = $checkCode->id;
		$updateCode->codesused = $checkCode->codesused+1;
		$DB->update_record('block_enrol_code', $updateCode);
		
		$user = $DB->get_record('user', array('id' => $insert));
		$a = new stdClass();
		$a->firstname = $_POST["email"];
		$a->username = $_POST["email"];
		$a->password = $pass;
		$a->link = html_writer::tag('a', $CFG->wwwroot.'/login/index.php', array('href' => $CFG->wwwroot.'/login/index.php'));
		$subject = get_string('credentials_email_subject', 'block_enrol_code');
		$plaintext_body = get_string('credentials_email_body', 'block_enrol_code', $a);
    // Html version
    $html_body = nl2br(get_string('credentials_email_body', 'block_enrol_code', $a));
    if(email_to_user($user, 'noreply@els.prepworks2400.com', $subject, $plaintext_body, $html_body)){

    	$email_exists = true;
    }

   	?>
		<!-- <form id="myForm" action="" method="post">
    	<input type="hidden" name="username" value="<?php echo $_POST["email"]; ?>">
    	<input type="hidden" name="password" value="<?php echo $pass ; ?>">
		</form> -->
		<!-- <script type="text/javascript">
			document.getElementById('myForm').submit();
		</script> -->

		<?php
	}
}

echo $OUTPUT->header();

?>
<link rel="stylesheet" type="text/css" href="style.css">
<?php
if(!$next) {
	if($email_exists) {

?>
	<div class="enrolcodeform enrolcodeform1">
	<div class="login_logo"><img src="images/login_logo_moodle.png"></div>
	<div class="box generalbox enrolcodebox enrolcodeinst1"><?php echo get_string('thank_you_heading', 'block_enrol_code'); ?></div> <br>
	<form class="mform" id="mform1" accept-charset="utf-8" method="post" action="<?php echo $CFG->wwwroot.'/login/index.php'?>" autocomplete="off">
		<fieldset class="hidden">
			<div>
				<div class="fitem required fitem_ftext" id="fitem_id_code">
					<div class="fitemtitle left">
						<label for="id_code"><?php echo get_string('thank_you_message', 'block_enrol_code'); ?></label>
					</div>
					
				</div>
				
				<div class="fitem fitem_actionbuttons fitem_fsubmit" id="fitem_id_next">
					<div class="felement fsubmit">
						<input type="submit" id="id_next" value="Next" name="login_next" class="enrolcodesubmit">
					</div>
				</div>
				<div class="fdescription required" style="    font-size: 15px;"><?php echo get_string('required_field', 'block_enrol_code'); ?><a href="https://coactive.com" class="help_log_icon">?</a></div>
			</div>
		</fieldset>
	</form>
</div>
<?php } else {?>
		<div class="red bottom"><?php if($email_exists_message) { echo get_string('already_account', 'block_enrol_code'); } ?></div>
	<?php	

	?>

<div class="enrolcodeform enrolcodeform1">
	<div class="login_logo"><img src="images/login_logo_moodle.png"></div>
	<!-- <div class="box generalbox enrolcodebox enrolcodeinst1"><?php echo get_string('code_instructions', 'block_enrol_code'); ?></div> --><br>
	<form class="mform" id="mform1" accept-charset="utf-8" method="post" action="" autocomplete="off">
		<fieldset class="hidden">
			<div>
				<div class="fitem required fitem_ftext" id="fitem_id_code">
					<div class="fitemtitle">
						<label for="id_code"><?php echo get_string('code', 'block_enrol_code'); ?>*</label>
					</div>
					<div class="felement ftext">
						<input type="text" id="id_code" name="code">
						<div class="red"><?php if($flag) { echo get_string('wrong_code', 'block_enrol_code'); } if($exceeded) { echo get_string('codes_exceeded', 'block_enrol_code'); } ?></div>
					</div>
				</div>
				<div class="fitem required fitem_ftext" id="fitem_id_code">
					<div class="fitemtitle">
						<label for="id_code"><?php echo get_string('email', 'block_enrol_code'); ?>*</label>
					</div>
					<div class="felement ftext">
						<input type="text" id="id_code" name="email">
						<div class="red"><?php if($flag) { echo get_string('wrong_email', 'block_enrol_code'); } if($exceeded) { echo get_string('codes_exceeded', 'block_enrol_code'); } ?></div>
					</div>
				</div>
				<div class="fitem fitem_actionbuttons fitem_fsubmit" id="fitem_id_next">
					<div class="felement fsubmit">
						<input type="submit" id="id_next" value="Next" name="next" class="enrolcodesubmit">
					</div>
				</div>
				<div class="fdescription required" style="    font-size: 15px;"><?php echo get_string('required_field', 'block_enrol_code'); ?><a href="https://coactive.com" class="help_log_icon">?</a></div>
			</div>
		</fieldset>
	</form>
</div>

<?php
}
} else {
	?>
	<div class="enrolcodeform enrolcodeform1">
		<div class="login_logo"><img src="images/login_logo_moodle.png"></div>
	<div class="box generalbox enrolcodebox enrolcodeinst1"><?php echo get_string('enter_details', 'block_enrol_code'); ?></div> <br>
		<form class="mform" id="mform1" accept-charset="utf-8" method="post" action="" autocomplete="off">
			<fieldset class="hidden">
				<div>
				
					<div class="fitem required fitem_ftext" id="fitem_id_code">
						<div class="fitemtitle left">
							<label for="id_code"><?php echo get_string('firstname'); ?>*</label>
						</div>
						<div class="felement ftext">
							<input type="text" id="id_code" name="firstname" required>
						</div>
					</div>
					
					<div class="fitem required fitem_ftext" id="fitem_id_code">
						<div class="fitemtitle left">
							<label for="id_code"><?php echo get_string('lastname', 'block_enrol_code'); ?>*</label>
						</div>
						<div class="felement ftext">
							<input type="text" id="id_code" name="lastname" required>
						</div>
					</div>
					
					<div class="fitem required fitem_ftext" id="fitem_id_code">
						<!-- <div class="fitemtitle">
							<label for="id_code"><?php echo get_string('email'); ?>*</label>
						</div> -->
						<div class="felement ftext">
							<input type="hidden" id="id_code" name="email" value="<?php echo $_POST['email']?>">
						</div>
					</div>
					
					<input type="hidden" name="code" value="<?php echo $_POST["code"] ?>">
					
					<div class="fitem fitem_actionbuttons fitem_fsubmit" id="fitem_id_next">
						<div class="felement fsubmit">
							<input type="submit" id="id_next" value="Next" name="submit" class="enrolcodesubmit">
						</div>
					</div>
					<div class="fdescription required" style="    font-size: 15px;"><?php echo get_string('required_field', 'block_enrol_code'); ?><a href="https://coactive.com" class="help_log_icon">?</a></div>
				</div>

			</fieldset>
		</form>
	</div>
	
	<?php
	
}
?>



<?php
echo $OUTPUT->footer();