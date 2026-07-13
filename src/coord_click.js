import Map from 'ol/Map.js';
import View from 'ol/View.js';
import TileLayer from 'ol/layer/Tile.js';
import OSM from 'ol/source/OSM.js';

import VectorLayer from 'ol/layer/Vector.js';
import VectorSource from 'ol/source/Vector.js';

import Draw from 'ol/interaction/Draw.js';

import { fromLonLat, toLonLat } from 'ol/proj.js';

import { getDistance } from 'ol/sphere.js';

import Feature from 'ol/Feature.js';

import Polygon from 'ol/geom/Polygon.js';

import XYZ from 'ol/source/XYZ.js';

const source = new VectorSource();

var currentDots = [];
var allZones = [];
window.allZones = allZones;

var zoneCount = 0;

const features = [];

import Style from 'ol/style/Style.js';
import Stroke from 'ol/style/Stroke.js';
import Fill from 'ol/style/Fill.js';

const highlightStyle = new Style({
    stroke: new Stroke({
        color: '#ff0000',
        width: 4
    }),
    fill: new Fill({
        color: 'rgba(255,0,0,0.2)'
    })
});

const vectorLayer = new VectorLayer({
  source: source,
});

const satellite = new TileLayer({
  source: new XYZ({
    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
  })
});

const map = new Map({
    target: 'map',
    layers: [
        satellite,
        vectorLayer
    ],
    view: new View({
        center: fromLonLat([2.2137, 46.2276]),
        zoom: 5,
    })
});

/*
const map = new Map({
  target: 'map',

  layers: [
    new TileLayer({
      source: new OSM(),
    }),
    vectorLayer,
  ],

  view: new View({
    center: fromLonLat([2.2137, 46.2276]), // France
    zoom: 5,
  }),
});
*/
// Affiche les coordonnées GPS du clic
/*
map.on('click', function (event) {

    if (allZones.length == zoneCount) {

        const [lon, lat] = toLonLat(event.coordinate);

        console.log(
            `Latitude : ${lat.toFixed(6)} | Longitude : ${lon.toFixed(6)}`
        );

        currentDots.push([lat.toFixed(6), lon.toFixed(6)]);

        console.log("Current Dots :");
        console.log(currentDots);

    } else {
		add_zone_in_list();
    }

});
*/
// Outil de dessin de polygone
const draw = new Draw({
  source: source,
  type: 'Polygon',
});

/*******************************/
// Click on Map more Reliable //
/*******************************/

let lastPointCount = 0;
let lastCoord = null;

draw.on('drawstart', function (event) {

    lastPointCount = 0;
    lastCoord = null;

    const sketch = event.feature;

    sketch.getGeometry().on('change', function (evt) {

        const coords = evt.target.getCoordinates()[0];

        // On retire le point temporaire de la souris
        const realPointCount = coords.length - 1;

        if (realPointCount <= 0) {
            return;
        }

        const coord = coords[realPointCount - 1];

        if (
            realPointCount > lastPointCount &&
            (
                lastCoord === null ||
                JSON.stringify(coord) !== JSON.stringify(lastCoord)
            )
        ) {

            const [lon, lat] = toLonLat(coord);

			if (allZones.length == zoneCount) {
				console.log("NOUVEAU POINT AJOUTÉ");

				currentDots.push([
					lat.toFixed(6),
					lon.toFixed(6)
				]);

				console.log("Current Dots :", currentDots);
			}

            lastPointCount = realPointCount;
            lastCoord = coord;
        }

    });

});

/*******************************/
/*******************************/

map.addInteraction(draw);

// Quand l'utilisateur termine une zone
draw.on('drawend', function (event) {
	
 features.push(event.feature);

  const polygon = event.feature.getGeometry();

  const coordinates3857 = polygon.getCoordinates()[0];

  const gpsCoordinates = coordinates3857.map(coord => {
    const [lon, lat] = toLonLat(coord);
    return [lon, lat];
  });

  let maxDistance = 0;
  let pointA = null;
  let pointB = null;

  for (let i = 0; i < gpsCoordinates.length; i++) {

    for (let j = i + 1; j < gpsCoordinates.length; j++) {

      const distance = getDistance(
        gpsCoordinates[i],
        gpsCoordinates[j]
      );

      if (distance > maxDistance) {
        maxDistance = distance;
        pointA = gpsCoordinates[i];
        pointB = gpsCoordinates[j];
      }
    }
  }

  console.log(
    `Distance maximale : ${(maxDistance / 1000).toFixed(2)} km`
  );

  if (maxDistance > 7000) {
    alert(
      `La zone est trop grande.\n\nDistance maximale : ${(maxDistance / 1000).toFixed(2)} km\n\nMaximum autorisé : 7 km`
    );
	
	setTimeout(() => {
		source.removeFeature(event.feature);
	}, 0);
		
	currentDots = [];
	return;
	
  } else {
	  allZones.push(currentDots);
	  add_zone_in_list();
	  console.log("All Zones =>");
	  console.log(allZones); 
	  
	  
	  setTimeout(() => {
		currentDots = [];
	 }, 100);
	  
  }

  //console.log('Point A :', pointA);
  //console.log('Point B :', pointB);

});

document.getElementById("zone_list").addEventListener("click", function (e) {

    if (e.target.classList.contains("btnEffacer")) {

        const index = Number(e.target.dataset.index);
		
		// Managing delete
		var coords_to_send = JSON.stringify(allZones[index]);
		var coords_to_send_clean = JSON.stringify(allZones[index]).replace(/"/g, '');
			
		console.log('.btn-primary[data-coords="'+coords_to_send_clean+'"]');
		console.log(coords_to_send_clean);
		
		
		const btn = document.querySelector('.btn-primary[data-coords="'+coords_to_send_clean+'"]');
		console.log(!(btn && btn.offsetParent !== null));
		
		if (!(btn && btn.offsetParent !== null)) {
			sup_specific_zone(coords_to_send);
		}
		//---//

        source.removeFeature(features[index]);
        features.splice(index, 1);
		
		document.getElementById("zone"+index).remove();
		
		const zones = document.getElementsByClassName("zone_element");

		document.querySelectorAll(".zone_element").forEach((zone, index) => {

			// Renomme l'id de la div
			zone.id = `zone${index}`;
			
			//Mettre à jour bouton modal
			const button = zone.querySelector("button");
			button.dataset.id = index;
			button.dataset.coords = JSON.stringify(allZones[index]);

			// Renomme le titre
			const h2 = zone.querySelector("h2");
			if (h2) {
				h2.textContent = `Zone ${index + 1}`;
			}

			// Met à jour le bouton Remove
			const btn = zone.querySelector(".btnEffacer");
			if (btn) {
				btn.dataset.index = index;
			}
		});
			
		allZones.splice(index, 1);
		zoneCount = document.getElementsByClassName("zone_element").length;
		

    }

});

async function sup_specific_zone(coords) {
	
	const response = await fetch("http://localhost/routing.php?route=delete_specific_zone", {
			method: "POST",
			headers: {
				"Content-Type": "application/json"
			},
			body: JSON.stringify({
				coords: coords
			})
		});
		
	const data = await response.json();
	
	if (data !== "Nothing to do") {
		alert(data);
	}

}

document.getElementById("zone_list").addEventListener("mouseover", function (e) {

    const zone = e.target.closest(".zone_element");
    if (!zone) return;

    const index = Number(zone.querySelector(".btnEffacer").dataset.index);

    features[index].setStyle(highlightStyle);
});

document.getElementById("zone_list").addEventListener("mouseout", function (e) {

    const zone = e.target.closest(".zone_element");
    if (!zone) return;

    const index = Number(zone.querySelector(".btnEffacer").dataset.index);

    features[index].setStyle(null); // Retour au style par défaut
});

/**
 * Dessine un polygone sur une couche OpenLayers.
 *
 * @param {Array<Array<number>>} coordinates Tableau de coordonnées [[x1,y1], [x2,y2], ...]
 * @param {VectorSource} source Source vectorielle dans laquelle ajouter le polygone.
 * @returns {Feature} Le polygone créé.
 */
function drawPolygon(coordinates) {

    const ring = coordinates.map(coord =>
        fromLonLat([
            parseFloat(coord[1]), // longitude
            parseFloat(coord[0])  // latitude
        ])
    );

    // Ferme le polygone
    ring.push(ring[0]);

    const feature = new Feature({
        geometry: new Polygon([ring])
    });

    feature.setStyle(new Style({
        stroke: new Stroke({
            color: '#ff0000',
            width: 2
        }),
        fill: new Fill({
            color: 'rgba(255,0,0,0.3)'
        })
    }));

    source.addFeature(feature);
	features.push(feature);

    return feature;
}

function add_zone_in_list() {
	zoneCount = allZones.length;

        let dataCoordsJ = "[";
        let html = `<div id="zone${zoneCount-1}" class="zone_element border rounded p-3 mb-3">
                        <h2>Zone ${zoneCount}</h2>`;

        for (let i = 0; i < allZones[zoneCount - 1].length; i++) {

            const coords = allZones[zoneCount - 1][i];

            html += `[${coords[0]}, ${coords[1]}] `;

            dataCoordsJ += `[${coords[0]},${coords[1]}],`;
        }

        dataCoordsJ = dataCoordsJ.slice(0, -1) + "]";

        html += `
            <br><br>

            <span
                class="btnEffacer text-primary"
                style="cursor:pointer"
                data-index="${zoneCount - 1}" data-coords='`+JSON.stringify(allZones[zoneCount - 1])+`'>
                Remove
            </span>

            <div class="mt-2">
                <button
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#maModal"
                    data-id="${zoneCount - 1}"
                    data-coords='${dataCoordsJ}'
                    data-nom="Zone ${zoneCount}">
                    Declare
                </button>
            </div>
        </div>`;

        document.getElementById("zone_list").innerHTML = document.getElementById("zone_list").innerHTML += html;
}

async function chargerZones() {
		const response = await fetch("http://localhost/routing.php?route=get_zones_for_specific_user", {
			method: "POST",
			headers: {
				"Content-Type": "application/json"
			},
			body: JSON.stringify({
				user_id: 1
			})
		});

		const data = await response.json();
		
		data.result.forEach(zone => {
		const coords = JSON.parse(zone.coords);
				allZones.push(coords);
				add_zone_in_list();
				
				var bouton_from_last_action = document.querySelector("button[data-coords='"+JSON.stringify(coords).replaceAll('"','')+"']");
				var parent = bouton_from_last_action.parentElement;
				bouton_from_last_action.style.display = 'none';
				parent.innerHTML += parent.innerHTML + date_zone_area(zone.start_date, zone.end_date); 
				
				add_status_div_area(JSON.stringify(coords));
				
		});
		
		if (typeof allZones[0] != "undefined") {
			map.getView().animate({
			  center: fromLonLat([allZones[0][0][1], allZones[0][0][0]]), // Grenoble
			  zoom: 12,
			  duration: 1000
			});			
		}
}

function add_status_div_area(coords) {
		var bouton_from_last_action = document.querySelector("button[data-coords='"+coords.replaceAll('"','')+"']");
				var parent = bouton_from_last_action.parentElement;
				bouton_from_last_action.style.display = 'none';
				
				parent.innerHTML = parent.innerHTML 
				+ "<div class='status' id='status_"+coords+"' data-coords='"+coords+"'>"
				+ "<img class='status_img' style='width: 19px;padding-bottom: 3px;margin-right: 2px;' src='http://localhost/img/anim_orange.gif'>" 
				+ "<span class ='blink status_message' style='font-weight: bold;'></span>";
				+ "</div>"
}

const form = document.querySelector("#declare_form");

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const response = await fetch("http://localhost/routing.php?route=declare_zone", {
        method: "POST",
        body: new FormData(form)
    });

    const data = await response.json();
    alert(data.msg);
	bootstrap.Modal.getOrCreateInstance(document.getElementById("maModal")).hide();
	
	if (data.success == true) {
	/*	var bouton_from_last_action = document.querySelector("button[data-coords='"+form.coords.value.replaceAll('"','')+"']");
		var parent = bouton_from_last_action.parentElement;
		bouton_from_last_action.style.display = 'none';
		
		parent.innerHTML = parent.innerHTML 
		+ "<div class='status' id='status_"+form.coords.value+"' data-coords='"+form.coords.value+"'>"
		+ "<img style='width: 19px;padding-bottom: 3px;margin-right: 2px;' src='http://localhost/img/anim_orange.gif'>" 
		+ "<span class ='blink status_message' style='font-weight: bold;'>Declared & before start date.</span>";
		+ "</div>";*/
		
		var bouton_from_last_action = document.querySelector("button[data-coords='"+form.coords.value.replaceAll('"','')+"']");
		var parent = bouton_from_last_action.parentElement;
		
		const options = {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		};
		
		parent.innerHTML += date_zone_area(form.start_date.value, form.end_date.value);
		add_status_div_area(form.coords.value);
		
	}
	
});

function date_zone_area(start_date, end_date) {
	const options = {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		};

		const debut = new Date(start_date).toLocaleString('fr-FR', options);
		const fin = new Date(end_date).toLocaleString('fr-FR', options);

		return '<span style="font-weight: bold;">'+`${debut} <br /> ${fin}`+'</span><br /><br />';
}

async function init() {
    await chargerZones();
	
	allZones.forEach(zone => {
		drawPolygon(zone);
	});
}

init();
	