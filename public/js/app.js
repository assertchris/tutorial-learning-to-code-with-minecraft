function Game() {
    return React.createElement(
        "h1", null, "Game"
    )
}

class Player extends React.Component {
    constructor(...params) {
        super(...params)

        this.onClick = this.onClick.bind(this)
    }

    onClick(e) {
        e.preventDefault()

        this.props.onJoin(this.props.name)
    }

    render() {
        return React.createElement(
            "a",
            {
                "onClick": this.onClick,
                "className": "player",
                "href": "#",
            },
            this.props.name
        )
    }
}

function PlayerList(props) {
    return React.createElement(
        "div",
        {
            "className": "player-list"
        },
        [
            React.createElement(
                "h1",
                {
                    "key": "heading",
                },
                "Player List"
            ),
            props.players.map(player => {
                return React.createElement(
                    Player,
                    {
                        "key": player,
                        "name": player,
                        "onJoin": props.onJoin,
                    }
                )
            }),
        ]
    )
}

class App extends React.Component {
    constructor(...params) {
        super(...params)

        this.state = {
            "player": null,
            "players": [],
        }

        this.onJoin = this.onJoin.bind(this)
        this.onMessage = this.onMessage.bind(this)
    }

    send(payload) {
        this.socket.send(JSON.stringify(payload))
    }

    onJoin(name) {
        this.setState({
            "player": name,
        })

        this.send({
            "type": "join",
            "data": name,
        })
    }

    onMessage(e) {
        let parsed = JSON.parse(e.data)

        console.log(parsed)

        if (parsed.type === "get-players") {
            this.setState({
                "players": parsed.data
            })
        }

        if (parsed.type === "player-joined") {
            this.setState({
                "players": this.state.players
                    .filter(player => player !== parsed.data)
                    .concat([parsed.data])
            })
        }

        if (parsed.type === "player-left") {
            this.setState({
                "players": this.state.players
                    .filter(player => player !== parsed.data)
            })

            if (this.state.player == parsed.data) {
                this.setState({
                    "player": null,
                })
            }
        }
    }

    componentWillMount() {
        this.socket = new WebSocket(
            "ws://127.0.0.1:8080/ws"
        )

        this.socket.addEventListener("open", e => {
            this.socket.addEventListener(
                "message", this.onMessage
            )

            this.send({
                "type": "get-players"
            })
        })
    }

    componentWillUnmount() {
        this.socket.removeEventListener(
            "message", this.onMessage
        )

        this.socket = null
    }

    render() {
        let child = null

        if (this.state.player) {
            child = React.createElement(Game, {
                "player": this.state.player
            })
        } else {
            child = React.createElement(PlayerList, {
                "players": this.state.players,
                "onJoin": this.onJoin
            })
        }

        return React.createElement(
            "div", {"className": "message"}, child
        )
    }
}

ReactDOM.render(
    React.createElement(App),
    document.querySelector(".app")
)
