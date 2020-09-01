var currentId = 0;
var x;
(function update(){
    $.get({
        url: 'checkState.php', 
        data: {id: currentId},
        success: function(data) {
            if(data == "started"){
                window.open("game.php", "_self");
            }
            else if(data != ""){
                x = JSON.parse(data);
                var newContent = "";
                newContent += '<tr><td align="center"><b>Players</b>\n';
                newContent += '<tr><td align="center">'+x.players[0].username+" <b>(owner)</b>\n";
                for(i=1; i<x.players.length; i++){
                    newContent += '<tr><td align="center">'+x.players[i].username+"\n";
                }
                $("#players").html(newContent);
                currentId = x.globalStateId;
            }
        },
        complete: function() {
            // Schedule the next request
            setTimeout(update, 1000);
        }
    });
})();