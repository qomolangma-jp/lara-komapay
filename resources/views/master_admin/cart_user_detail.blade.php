@extends('layouts.master_layout')

@section('title', 'ユーザーカート詳細')

@section('content')
<style>
    body {
        margin: 0;
        padding: 0;
        background: #faf8ef;
        font-family: 'Trebuchet MS', sans-serif;
    }
    
    #gameContainer {
        width: 100%;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #faf8ef;
    }
    
    .game-wrapper {
        text-align: center;
    }
    
    .game-board {
        display: grid;
        grid-template-columns: repeat(4, 107px);
        gap: 15px;
        background-color: #bbada0;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        margin: 20px auto;
    }
    
    .tile {
        width: 107px;
        height: 107px;
        background-color: #cdc1b4;
        border-radius: 3px;
        font-size: 35px;
        font-weight: bold;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #776e65;
        transition: all 0.15s ease-in-out;
    }
    
    .tile.empty {
        background-color: #cdc1b4;
        color: transparent;
    }
    
    .tile-2 { background-color: #eee4da; color: #776e65; }
    .tile-4 { background-color: #ede0c8; color: #776e65; }
    .tile-8 { background-color: #f2b179; color: #f9f6f2; font-weight: bold; }
    .tile-16 { background-color: #f59563; color: #f9f6f2; font-weight: bold; }
    .tile-32 { background-color: #f67c5f; color: #f9f6f2; font-weight: bold; }
    .tile-64 { background-color: #f65e3b; color: #f9f6f2; font-weight: bold; }
    .tile-128 { background-color: #edcf72; color: #095f6b; font-size: 30px; font-weight: bold; }
    .tile-256 { background-color: #edcc61; color: #095f6b; font-size: 30px; font-weight: bold; }
    .tile-512 { background-color: #edc850; color: #095f6b; font-size: 30px; font-weight: bold; }
    .tile-1024 { background-color: #edc53f; color: #095f6b; font-size: 25px; font-weight: bold; }
    .tile-2048 { background-color: #edc22e; color: #095f6b; font-size: 25px; font-weight: bold; }
    
    .score-board {
        font-size: 24px;
        margin: 10px 0;
        font-weight: bold;
        color: #776e65;
    }
    
    .game-over-message {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 40px 60px;
        border-radius: 6px;
        font-size: 32px;
        font-weight: bold;
        display: none;
        z-index: 1000;
    }
    
    .game-over-message.show {
        display: block;
    }
</style>

<div id="gameContainer">
    <div class="game-wrapper">
        <div class="score-board">スコア: <span id="score">0</span></div>
        <div class="game-board" id="gameBoard"></div>
        <div style="margin-top: 20px; color: #776e65; font-size: 14px;">キーボード操作</div>
    </div>
</div>

<div id="gameOverMessage" class="game-over-message">
    <div>GAME OVER</div>
    <div style="font-size: 18px; margin-top: 20px;">Rキー: リスタート</div>
</div>

<script>
class Game2048 {
    constructor() {
        this.board = Array(16).fill(0);
        this.score = 0;
        this.gameOver = false;
        this.init();
    }
    
    init() {
        this.board = Array(16).fill(0);
        this.score = 0;
        this.gameOver = false;
        this.addNewTile();
        this.addNewTile();
        this.render();
    }
    
    addNewTile() {
        const empty = this.board.map((v, i) => v === 0 ? i : null).filter(v => v !== null);
        if (empty.length === 0) return;
        const idx = empty[Math.floor(Math.random() * empty.length)];
        this.board[idx] = Math.random() < 0.9 ? 2 : 4;
    }
    
    move(direction) {
        if (this.gameOver) return;
        
        const oldBoard = [...this.board];
        
        if (direction === 'left') this.slideLeft();
        else if (direction === 'right') this.slideRight();
        else if (direction === 'up') this.slideUp();
        else if (direction === 'down') this.slideDown();
        
        if (oldBoard.toString() !== this.board.toString()) {
            this.addNewTile();
        }
        
        if (!this.canMove()) {
            this.gameOver = true;
        }
        
        this.render();
    }
    
    slideLeft() {
        for (let i = 0; i < 4; i++) {
            this.mergeLine(i * 4, i * 4 + 4, 1);
        }
    }
    
    slideRight() {
        for (let i = 0; i < 4; i++) {
            this.mergeLine(i * 4 + 3, i * 4 - 1, -1);
        }
    }
    
    slideUp() {
        for (let i = 0; i < 4; i++) {
            this.mergeLine(i, i + 16, 4);
        }
    }
    
    slideDown() {
        for (let i = 0; i < 4; i++) {
            this.mergeLine(i + 12, i - 4, -4);
        }
    }
    
    mergeLine(start, end, step) {
        const line = [];
        for (let i = start; i !== end; i += step) {
            if (this.board[i] !== 0) line.push(this.board[i]);
        }
        
        for (let i = 0; i < line.length - 1; i++) {
            if (line[i] === line[i + 1]) {
                line[i] *= 2;
                this.score += line[i];
                line.splice(i + 1, 1);
            }
        }
        
        while (line.length < 4) line.push(0);
        
        for (let i = start, idx = 0; i !== end; i += step, idx++) {
            this.board[i] = line[idx];
        }
    }
    
    canMove() {
        for (let i = 0; i < 16; i++) {
            if (this.board[i] === 0) return true;
            if (i % 4 < 3 && this.board[i] === this.board[i + 1]) return true;
            if (i < 12 && this.board[i] === this.board[i + 4]) return true;
        }
        return false;
    }
    
    render() {
        const boardEl = document.getElementById('gameBoard');
        boardEl.innerHTML = '';
        
        for (let i = 0; i < 16; i++) {
            const tile = document.createElement('div');
            const value = this.board[i];
            tile.className = 'tile' + (value ? ' tile-' + value : ' empty');
            if (value > 0) tile.textContent = value;
            boardEl.appendChild(tile);
        }
        
        document.getElementById('score').textContent = this.score;
        
        if (this.gameOver) {
            document.getElementById('gameOverMessage').classList.add('show');
        } else {
            document.getElementById('gameOverMessage').classList.remove('show');
        }
    }
}

let game = new Game2048();

document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') { e.preventDefault(); game.move('left'); }
    else if (e.key === 'ArrowRight') { e.preventDefault(); game.move('right'); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); game.move('up'); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); game.move('down'); }
    else if (e.key.toLowerCase() === 'r') { game.init(); }
});
</script>
@endsection
