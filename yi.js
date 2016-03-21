tr=document.getElementsByClassName('stats-chart-gviz')[0].getElementsByTagName('svg')[0].nextSibling.getElementsByTagName('tr');
ret="";
for(var i=1;i<tr.length;i++){tds=tr[i].getElementsByTagName('td');ret+=(tds[0].innerText+","+tds[1].innerText+"\r\n");}
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
