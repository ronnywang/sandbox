tr=document.getElementsByClassName('stats-chart-gviz')[0].getElementsByTagName('svg')[0].nextSibling.getElementsByTagName('tr');
ret="";
var prev_value = 0;
for(var i = 1; i < tr.length; i ++){
    tds = tr[i].getElementsByTagName('td');
    ret += (tds[0].innerText + "," + tds[1].innerText + "," + (parseInt(tds[1].innerText) - prev_value) + "\r\n");
    prev_value = parseInt(tds[1].innerText);
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

saveData(ret, "insight.csv");
