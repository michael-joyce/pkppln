
$(document).ready(function () {
    $("a.js-confirm").each(function () {
        var $this = $(this);
        $this.click(function(){
            return window.confirm($this.data('confirm'));
        });
    });
});
