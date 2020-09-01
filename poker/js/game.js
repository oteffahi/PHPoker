var currentId = -1;
var game;
(function update(){
    $.get({
        url: 'update.php', 
        data: {id: currentId},
        success: function(data) {
            if(data != ""){
                game = JSON.parse(data);
                currentId = game.globalStateId;
                
                //pot
                $("#pot").html(game.pot+"$");
                
                //table cards
                var suits = {};
                suits["Spades"] = "&spades;";
                suits["Hearts"] = "&hearts;";
                suits["Clubs"] = "&clubs;";
                suits["Diamonds"] = "&diams;";
                
                //clear table content
                for(i=1; i<6; i++)
                    $("#table"+(i)).html("");
                
                for(i=0; i<game.tableCards.length; i++){
                    $("#table"+(i+1)).html(game.tableCards[i].rank+suits[game.tableCards[i].suit]);
                }
                
                //current bets and chips
                for(i=0; i<game.players.length; i++){
                    var bet = game.players[i].currentBet+"$";
                    if(game.playerTurn == i){
                        bet = '<font color="red">'+bet+'</font>';
                    }
                    $("#bet"+i).html(bet);
                    $("#chips"+i).html(game.players[i].chips+"$");
                    if(game.players[i].hand){
                        var card1 = game.players[i].hand[0].rank+suits[game.players[i].hand[0].suit];
                        var card2 = game.players[i].hand[1].rank+suits[game.players[i].hand[1].suit];
                        $("#card"+i+"1").html(card1);
                        $("#card"+i+"2").html(card2);
                    }
                    else{
                        $("#card"+i+"1").html("");
                        $("#card"+i+"2").html("");
                    }
                }
                
                //log
                var logContent = "";
                for(i=0; i<game.log.length; i++){
                    logContent += game.log[i]+"<br>"; 
                }
                $("#log").html(logContent);
                var log = document.getElementById('log');
                log.scrollTop = log.scrollHeight;
            }
        },
        complete: function() {
            // Schedule the next request
            setTimeout(update, 1000);
        }
    });
})();

(function(){
    $("#call").on('click', function(){
        if(game.playerTurn == myId){
            $.get("action.php", {amount: 0});
        }
    });
    
    $("#fold").on('click', function(){
        if(game.playerTurn == myId){
            $.get("action.php", {amount: -1});
        }
    });
    
    $("#bet").on('click', function(){
        if(game.playerTurn == myId){
            var amount = parseInt($("#betValue").val());
            if(!isNaN(amount)){
                $.get("action.php", {amount: amount});
                $("#betValue").val("");
            }
        }
    });
})();