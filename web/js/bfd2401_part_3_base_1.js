
$(document).ready(function () {
    $("a.js-confirm").each(function () {
        var $this = $(this);
        $this.click(function () {
            return window.confirm($this.data('confirm'));
        });
    });

    var hostname = window.location.hostname.replace('www.', '');
    $('a').each(function () {
        var link_host = $(this).prop('href', url).prop('hostname');
        
        console.log([link_host, hostname]);
        
        if (link_host !== hostname) {
            $(this).attr('target', '_blank');
        }
    });

});
