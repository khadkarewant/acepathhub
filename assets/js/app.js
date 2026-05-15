$(document).ready(function () {
    if ($.fn.DataTable && $("#datatable").length) {
        $("#datatable").DataTable();
    }
});

$(function () {
    const $sidebar  = $('#side_panel');
    const $overlay  = $('#sidebar-overlay');

    function openSidebar() {
        $sidebar.addClass('open');
        $overlay.addClass('active');
    }

    function closeSidebar() {
        $sidebar.removeClass('open');
        $overlay.removeClass('active');
    }

    $('#side_panel_icon').on('click', function () {
        $sidebar.hasClass('open') ? closeSidebar() : openSidebar();
    });

    $overlay.on('click', closeSidebar);

    $(document).on('pointerup', function (e) {
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

        if (!$(e.target).closest('.profile_menu').length) {
            $('.profile_submenu.open').removeClass('open');
        }
    });
});
