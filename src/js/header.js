$(function () {

    // Use pointer events (BEST for mobile)
    $(document).on('pointerup', function (e) {

        // Open sidebar
        if ($(e.target).closest('#side_panel_icon').length) {
            $('#side_panel').addClass('open');
            return;
        }

        // Close sidebar
        if ($(e.target).closest('.side_panel_cancel').length) {
            $('#side_panel').removeClass('open');
            return;
        }

        // Sidebar submenu toggle
        const menuToggle = $(e.target).closest('.menu-toggle');
        if (menuToggle.length) {
            e.preventDefault();
            menuToggle.next('.side-submenu').toggleClass('open');
            return;
        }

        // Profile menu toggle
        const profile = $(e.target).closest('.profile_menu');
        if (profile.length) {
            e.preventDefault();
            profile.find('.profile_submenu').toggleClass('open');
            return;
        }

        // Close profile when clicking outside
        $('.profile_submenu.open').removeClass('open');
    });

});
