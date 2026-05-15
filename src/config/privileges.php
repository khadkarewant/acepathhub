<?php
if (!isset($type)) {
    $type = null;
}
$delete_any_chat = "false";
$delete_own_chat = "false";

    $assign_product = "false";
    $create_user = "false";
    $create_course = "false";
    $create_subject = "false";
    $create_topic = "false";
    $create_mcq = "false";
    $create_package = "false";
    $create_promoCode = "false";
    $create_notification = "false"; 
    $create_product = "false";
    $view_user = "false";
    $view_course = "false";
    $view_subject = "false";
    $view_transaction = "false";
    $view_topic = "false";
    $view_mcq = "false";
    $view_package = "false";
    $view_promoCode = "false";
    $view_notification = "false";
    $view_user_list = "false";
    $view_course_list = "false";
    $view_subject_list = "false";
    $view_topic_list = "false";
    $view_mcq_list = "false";
    $view_package_list = "false";
    $view_promoCode_list = "false";
    $view_notification_list = "false";
    $modify_user = "false";
    $modify_course = "false";
    $modify_subject = "false";
    $modify_product = "false";
    $modify_topic = "false";
    $modify_mcq = "false";
    $modify_package = "false";
    $modify_promoCode = "false";
    $modify_notification = "false";
    $delete_user = "false";
    $delete_course = "false";
    $delete_subject = "false";
    $delete_topic = "false";
    $delete_mcq = "false";
    $delete_package = "false";
    $delete_promoCode = "false";
    $delete_notification = "false";
    $attend_exam ="false";
    $attempt_exam = "false";
    $verify_mcq = "false";
    $view_product = "false";
    $view_sales_bill = "false";
    $add_sales_bill = "false";
    
    if($type === "admin"){
        $delete_any_chat = "true";
        $delete_own_chat = "true"; // implicit, but good to be explicit

        $assign_product = "true";
        $create_user = "true";
        $create_course = "true";
        $create_subject = "true";
        $create_topic = "true";
        $verify_mcq = "true";
        $create_package = "true";
        $create_promoCode = "true";
        $create_notification = "true";
        $create_product = "true";
        $view_user = "true";
        $view_course = "true";
        $view_subject = "true";
        $view_topic = "true";
        $view_mcq = "true";
        $view_transaction = "true";
        $view_package = "true";
        $view_promoCode = "true";
        $view_notification = "true";
        $view_user_list = "true";
        $view_course_list = "true";
        $view_subject_list = "true";
        $view_topic_list = "true";
        $view_mcq_list = "true";
        $view_package_list = "true";
        $view_promoCode_list = "true";
        $view_notification_list = "true";
        $view_product = "true";
        $modify_user = "true";
        $modify_course = "true";
        $modify_subject = "true";
        $modify_topic = "true";
        $modify_mcq = "true";
        $modify_package = "true";
        $modify_promoCode = "true";
        $modify_product = "true";
        $modify_notification = "true";
        $delete_user = "true";
        $delete_course = "true";
        $delete_subject = "true";
        $delete_topic = "true";
        $delete_mcq = "true";
        $delete_package = "true";
        $delete_promoCode = "true";
        $delete_notification = "true";
        $view_sales_bill = "true";
        $add_sales_bill = "true";
    }

    if($type === "teacher"){
        $delete_own_chat = "true";

        $assign_product = "true";
        $create_promoCode = "true";
        $view_user = "true";
        $view_course = "true";
        $view_subject = "true";
        $view_topic = "true";
        $view_mcq = "true";
        $view_package = "true";
        $view_notification = "true";
        $view_user_list = "true";
        $view_course_list = "true";
        $view_subject_list = "true";
        $view_topic_list = "true";
        $view_mcq_list = "true";
        $view_package_list = "true";
        $view_product = "true";
        $view_notification_list = "true";
        $modify_mcq = "true";
        $modify_notification = "true";
    }
    if($type === "agent"){
        $delete_own_chat = "true";

        $assign_product = "true";
    }
    if($type === "data_entry"){
        $delete_own_chat = "true";

        $create_mcq = "true";
        $view_course = "true";
        $view_subject = "true";
        $view_topic = "true";
        $view_mcq = "true";
        $view_notification = "true";
        $view_course_list = "true";
        $view_subject_list = "true";
        $view_topic_list = "true";
        $view_mcq_list = "true";
        $view_notification_list = "true";
        $modify_mcq = "true";
        $modify_notification = "true";
    }

    if($type === "student"){
        $delete_own_chat = "true";

        $view_notification = "true";
        $view_product = "true";
        $view_course_list = "true";
        $view_subject_list = "true";
        $view_topic_list = "true";
        $view_package_list = "true";
        $view_notification_list = "true";
        $modify_notification = "true";
        $attend_exam ="true";
        $attempt_exam = "true";
    }

    if($type === "biller"){
        $delete_own_chat = "true";

        $view_sales_bill = "true";
        $add_sales_bill = "true";
    }
?>