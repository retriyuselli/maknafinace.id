(function ($) {
    "use strict";
    /*=================================
JS Index Here
==================================*/
    /*
01. Print and Download Button

00. Right Click Disable
00. Inspect Element Disable
*/
    /*=================================
JS Index End
==================================*/

    /*----------- 01. Print and Download Button ----------*/
    // Using html2pdf.js for high-quality text-based PDF output
    $("#download_btn")
        .off("click.simulasiDownload")
        .on("click.simulasiDownload", function (e) {
        e.preventDefault();
        if (this.dataset.busy === "1") {
            return;
        }
        this.dataset.busy = "1";
        var downloadSection = document.getElementById("download_section");
        if (!downloadSection) {
            this.dataset.busy = "0";
            return;
        }
        var pdfUrl = downloadSection.dataset.pdfUrl;
        if (!pdfUrl) {
            this.dataset.busy = "0";
            return;
        }
        var url = new URL(pdfUrl, window.location.origin);
        url.searchParams.set("download", "1");
        window.location.href = url.toString();
        var btn = this;
        setTimeout(function () {
            btn.dataset.busy = "0";
        }, 1200);
    });

    // Print Html Document with enhanced functionality
    $(".print_btn").on("click", function (e) {
        e.preventDefault();
        window.print();
    });

    // /*----------- 00. Right Click Disable ----------*/
    // window.addEventListener('contextmenu', function (e) {
    // // do something here...
    // e.preventDefault();
    // }, false);

    // /*----------- 00. Inspect Element Disable ----------*/
    // document.onkeydown = function (e) {
    // if (event.keyCode == 123) {
    // return false;
    // }
    // if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) {
    // return false;
    // }
    // if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) {
    // return false;
    // }
    // if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) {
    // return false;
    // }
    // if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) {
    // return false;
    // }
    // }
})(jQuery);
