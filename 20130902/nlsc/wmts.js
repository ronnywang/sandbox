var map;

function init() {
    
    map = new OpenLayers.Map({
        div: "map",
        projection: "EPSG:3857"
    });    
    
    var osm = new OpenLayers.Layer.OSM();

    // If tile matrix identifiers differ from zoom levels (0, 1, 2, ...)
    // then they must be explicitly provided.
    var matrixIds = new Array(26);
    for (var i=0; i<26; ++i) {
        matrixIds[i] = "EPSG:3857:" + i;
    }

    var wmts = new OpenLayers.Layer.WMTS({
        name: "Medford Buildings",
        url: "http://maps.nlsc.gov.tw/S_Maps/wmts",
        layer: "LANDSECT",
        matrixSet: "EPSG:3857",
        matrixIds: matrixIds,
        format: "image/png",
        style: "_null",
        opacity: 0.7,
        isBaseLayer: false
    });                

    map.addLayers([osm, wmts]);
    map.addControl(new OpenLayers.Control.LayerSwitcher());
    map.setCenter(new OpenLayers.LonLat(13500221.011127943, 2861141.0388860344), 9);
    
}
