$(document).ready(function(){

    if ($.fn.DataTable && $("#datatable").length) {
        $("#datatable").DataTable();
    }

    $("#side_panel_icon").click(function(){
        $(".side_panel_cancel").css("display", "block");
        $("#side_panel_icon").css("display", "none");
        $("#side_panel").css("display", "block");
        $("#side_menu_links").css("display", "block");
    });

    $(".side_panel_cancel").click(function(){
        $("#side_panel_icon").css("display", "block");
        $(".side_panel_cancel").css("display", "none");
        $("#side_panel").css("display", "none");
        $("#side_menu_links").css("display", "none");
    });

    $(".profile_menu").on("click", function(e){
        e.stopPropagation();
        $(".profile_submenu").toggle();
    });

    $(document).on("click", function(){
        $(".profile_submenu").hide();
    });

    $(document).keydown(function(e){
        if(e.key === "Escape"){
            $(".profile_submenu").hide();
        }
    });

});