if (!Element.prototype.matches) {
    Element.prototype.matches =
        Element.prototype.matchesSelector ||
        Element.prototype.mozMatchesSelector ||
        Element.prototype.msMatchesSelector ||
        Element.prototype.oMatchesSelector ||
        Element.prototype.webkitMatchesSelector ||
        function(s) {
            var matches = (this.document || this.ownerDocument).querySelectorAll(s)
            var i = matches.length

            while (--i >= 0 && matches.item(i) !== this) {}

            return i > -1
        }
}

var socket = new WebSocket("ws://localhost:8080/ws")

var players = []
var $players = document.querySelector(".players")

socket.addEventListener("open", function () {
    socket.send(JSON.stringify({
        "type": "get-players"
    }))
})

socket.addEventListener("message", function (e) {
    var parsed = JSON.parse(e.data)

    console.log(parsed);

    if (parsed.type === "players") {
        players = parsed.data
        $players.innerHTML = ""

        for (var player in players) {
            var $player = document.createElement("li");

            $player.innerHTML = "<a href='#' data-player='" + player + "'>" + player + "</a>"

            $players.appendChild($player)
        }
    }
})

var $body = document.querySelector("body")

$body.addEventListener("click", function(e) {
    if (e.target.matches(".players a")) {
        e.preventDefault()

        socket.send(JSON.stringify({
            "type": "join",
            "data": e.target.getAttribute("data-player")
        }))
    }
})
