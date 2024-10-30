!function (e, f) {
    e.async = 1;
    var src = `https://qcart.app/btn.js?trg=any`;
    var match = location.href.match(/test=[^&#]+/);
    if (match) src += `&${match[0]}`;
    e.src = src;
    f.parentNode.insertBefore(e, f);
}(document.createElement('script'), document.getElementsByTagName('script')[0]);