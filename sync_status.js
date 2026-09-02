//Set depending environement "dev" or "prod"
const mode = "prod";

var prefix = "";
if (mode == "dev") {
	prefix = "http://localhost/";
} 

function start() {
    setInterval(async () => {
		if (!document.querySelectorAll(".status").length == 0) {
			const coords_list = [...document.querySelectorAll(".status")]
				.map(item => item.dataset.coords);

			const response = await fetch(
				prefix + "routing.php?route=get_status",
				{
					method: "POST",
					headers: {
						"Content-Type": "application/json"
					},
					body: JSON.stringify({
						coords_list: coords_list
					})
				}
			);

			const result = await response.json();
			
			for (const [coords, statut] of Object.entries(result)) {
				console.log(statut);
				document.getElementById("status_" + coords).querySelector(".status_message").innerHTML = statut;

				if (statut.substring(0, 6) == "Stream") {
					document.getElementById("status_" + coords).querySelector(".status_img").src = prefix + "img/anim_vert.gif";
				} else if (statut.substring(0, 3) == "XXX") {
					document.getElementById("status_" + coords).querySelector(".status_img").src = prefix + "img/finish.png";
					const message = document.getElementById("status_" + coords)?.querySelector(".status_message");
					message?.classList.remove("blink");
				}
			
			}
		}

    }, 6000);
}

start();