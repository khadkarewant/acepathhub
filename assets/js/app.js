$(document).ready(function(){
    if ($.fn.DataTable && $("#datatable").length) {
        $("#datatable").DataTable();
    }
});

$(function () {
    $(document).on('pointerup', function (e) {
        if ($(e.target).closest('#side_panel_icon').length) {
            $('#side_panel').addClass('open');
            return;
        }
        if ($(e.target).closest('.side_panel_cancel').length) {
            $('#side_panel').removeClass('open');
            return;
        }
        const menuToggle = $(e.target).closest('.menu-toggle');
        if (menuToggle.length) {
            e.preventDefault();
            menuToggle.next('.side-submenu').toggleClass('open');
            return;
        }
        const profile = $(e.target).closest('.profile_menu');
        if (profile.length) {
            e.preventDefault();
            profile.find('.profile_submenu').toggleClass('open');
            return;
        }
        $('.profile_submenu.open').removeClass('open');
    });
});