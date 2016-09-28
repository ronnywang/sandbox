tr=document.getElementsByClassName('stats-chart-gviz')[0].getElementsByTagName('svg')[0].nextSibling.getElementsByTagName('tr');
if ('undefined' == typeof(yi_sep)) {
    yi_sep = ',';
}
if ('undefined' == typeof(yi_type)) {
    yi_type = 'csv';
}
ret="";
var prev_value = 0;
for(var i = 1; i < tr.length; i ++){
    tds = tr[i].getElementsByTagName('td');
    v = parseInt(tds[1].innerText.replace(/,/g, ''));
    ret += (tds[0].innerText + yi_sep + v + yi_sep + parseInt(v - prev_value) + "\r\n");
    prev_value = v;
}
var saveData = (function () {
    var a = document.createElement("a");
    document.body.appendChild(a);
    a.style = "display: none";
    return function (data, fileName) {
            blob = new Blob([data], {type: "octet/stream"}),
            url = window.URL.createObjectURL(blob);
        a.href = url;
        a.download = fileName;
        a.click();
        window.URL.revokeObjectURL(url);
    };
}());

saveData(ret, "insight." + yi_type);
