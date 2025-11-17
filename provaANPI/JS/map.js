(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;
   b=b[c]||(b[c]={});
   var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${
   c}apis.com/maps/api/js?`+e;
   d[q]=f;
   a.onerror=()=>h=n(Error(p+" could not load."));
   a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));
   d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})
       ({key: "EMPTY", v: "weekly"});

let map;

async function initMap() {
  // Scandiano position
  const position = { lat: 44.59864933199108, lng: 10.693915048462607};
  // Request needed libraries.
  //@ts-ignore
  const { Map } = await google.maps.importLibrary("maps");
  const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

  // The map, centered at Scandiano
  map = new Map(document.getElementById("map_container"), {
    zoom: 10,
    center: position,
    mapId: "DEMO_MAP_ID",
  });

  // The marker, positioned at Uluru
  const marker = new AdvancedMarkerElement({
    map: map,
    position: {lat: 44.60220143292395, lng: 10.68926579029954},
    title: "Tappa 14",
  });
}

initMap();