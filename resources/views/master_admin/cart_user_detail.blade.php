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
        position: relative;
        isolation: isolate;
        width: 503px;
        height: 503px;
        display: grid;
        grid-template-columns: repeat(4, 107px);
        gap: 15px;
        background-color: #bbada0;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        margin: 20px auto;
        overflow: hidden;
    }

    .game-board::before {
        content: '';
        position: absolute;
        inset: 15px;
        pointer-events: none;
        background-image:
            linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4),
            linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4),
            linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4),
            linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4), linear-gradient(#cdc1b4, #cdc1b4);
        background-size: 107px 107px;
        background-position:
            0 0, 122px 0, 244px 0, 366px 0,
            0 122px, 122px 122px, 244px 122px, 366px 122px,
            0 244px, 122px 244px, 244px 244px, 366px 244px,
            0 366px, 122px 366px, 244px 366px, 366px 366px;
        background-repeat: no-repeat;
        border-radius: 6px;
        z-index: 0;
    }
    
    .tile {
        position: absolute;
        top: 0;
        left: 0;
        width: 107px;
        height: 107px;
        border-radius: 3px;
        font-size: 35px;
        font-weight: bold;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #776e65;
        transition: transform 180ms cubic-bezier(0.2, 0.8, 0.2, 1), opacity 140ms ease, background-color 180ms ease, color 180ms ease;
        will-change: transform;
        z-index: 2;
    }
    
    .tile.empty {
        color: transparent;
    }

    .tile.spawn {
        opacity: 0.92;
    }

    .tile.merge {
        box-shadow: inset 0 0 0 4px rgba(255, 255, 255, 0.12);
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
        this.tiles = [];
        this.nextId = 1;
        this.score = 0;
        this.gameOver = false;
        this.animating = false;
        this.init();
    }
    
    init() {
        this.tiles = [];
        this.nextId = 1;
        this.score = 0;
        this.gameOver = false;
        this.addNewTile();
        this.addNewTile();
        this.render();
    }
    
    addNewTile() {
        const occupied = new Set(this.tiles.map(function(tile) { return tile.row + '-' + tile.col; }));
        const empty = [];
        for (let row = 0; row < 4; row++) {
            for (let col = 0; col < 4; col++) {
                if (!occupied.has(`${row}-${col}`)) {
                    empty.push({ row, col });
                }
            }
        }
        if (empty.length === 0) return;
        const spot = empty[Math.floor(Math.random() * empty.length)];
        this.tiles.push({
            id: this.nextId++,
            value: Math.random() < 0.9 ? 2 : 4,
            row: spot.row,
            col: spot.col,
            spawn: true,
            merge: false,
        });
    }
    
    move(direction) {
        if (this.gameOver || this.animating) return;

        const result = this.resolveMove(direction);
        if (!result.moved) return;

        this.tiles = result.tiles;
        this.score += result.scoreDelta;
        this.animating = true;
        this.render();

        window.setTimeout(function() {
            this.animating = false;
            this.tiles = this.tiles.map(function(tile) {
                return {
                    id: tile.id,
                    value: tile.value,
                    row: tile.row,
                    col: tile.col,
                    spawn: false,
                    merge: false,
                };
            });
            this.addNewTile();
            if (!this.canMove()) {
                this.gameOver = true;
            }
            this.render();
        }.bind(this), 180);
    }

    resolveMove(direction) {
        const groups = [];
        const source = this.tiles.map(function(tile) {
            return {
                id: tile.id,
                value: tile.value,
                row: tile.row,
                col: tile.col,
                spawn: tile.spawn,
                merge: tile.merge,
            };
        });
        let moved = false;
        let scoreDelta = 0;
        const nextTiles = [];

        if (direction === 'left' || direction === 'right') {
            for (let row = 0; row < 4; row++) {
                const line = source.filter(function(tile) { return tile.row === row; })
                    .sort(function(a, b) { return direction === 'left' ? a.col - b.col : b.col - a.col; });
                groups.push({ axis: 'row', index: row, line: line });
            }
        } else {
            for (let col = 0; col < 4; col++) {
                const line = source.filter(function(tile) { return tile.col === col; })
                    .sort(function(a, b) { return direction === 'up' ? a.row - b.row : b.row - a.row; });
                groups.push({ axis: 'col', index: col, line: line });
            }
        }

        groups.forEach(function(group) {
            let placeIndex = 0;
            for (let i = 0; i < group.line.length; i++) {
                const current = group.line[i];
                const next = group.line[i + 1];
                if (next && current.value === next.value) {
                    const target = this.targetPosition(group, placeIndex, direction);
                    const mergedValue = current.value * 2;
                    scoreDelta += mergedValue;
                    nextTiles.push({
                        id: next.id,
                        value: mergedValue,
                        row: target.row,
                        col: target.col,
                        merge: true,
                        spawn: false,
                    });
                    moved = moved || current.row !== target.row || current.col !== target.col || next.row !== target.row || next.col !== target.col;
                    placeIndex++;
                    i++;
                } else {
                    const target = this.targetPosition(group, placeIndex, direction);
                    nextTiles.push({
                        id: current.id,
                        value: current.value,
                        row: target.row,
                        col: target.col,
                        merge: false,
                        spawn: false,
                    });
                    moved = moved || current.row !== target.row || current.col !== target.col;
                    placeIndex++;
                }
            }
        }.bind(this));

        return { tiles: nextTiles, moved: moved, scoreDelta: scoreDelta };
    }
    
    targetPosition(group, offset, direction) {
        if (group.axis === 'row') {
            return {
                row: group.index,
                col: direction === 'left' ? offset : 3 - offset,
            };
        }

        return {
            row: direction === 'up' ? offset : 3 - offset,
            col: group.index,
        };
    }

    canMove() {
        const board = Array.from({ length: 4 }, function() { return Array(4).fill(0); });
        this.tiles.forEach(function(tile) {
            board[tile.row][tile.col] = tile.value;
        });

        for (let row = 0; row < 4; row++) {
            for (let col = 0; col < 4; col++) {
                if (board[row][col] === 0) return true;
                if (col < 3 && board[row][col] === board[row][col + 1]) return true;
                if (row < 3 && board[row][col] === board[row + 1][col]) return true;
            }
        }
        return false;
    }
    
    render() {
        const boardEl = document.getElementById('gameBoard');
        const existing = new Map(Array.from(boardEl.querySelectorAll('.tile')).map(function(el) { return [el.dataset.id, el]; }));
        const activeIds = new Set();

        this.tiles.forEach(function(tile) {
            const key = String(tile.id);
            activeIds.add(key);
            let el = existing.get(key);
            if (!el) {
                el = document.createElement('div');
                el.dataset.id = key;
                boardEl.appendChild(el);
            }

            el.className = 'tile tile-' + tile.value + (tile.spawn ? ' spawn' : '') + (tile.merge ? ' merge' : '');
            el.textContent = tile.value;
            el.style.transform = 'translate(' + (15 + tile.col * 122) + 'px, ' + (15 + tile.row * 122) + 'px) scale(1)';

            if (tile.spawn) {
                window.setTimeout(function() {
                    el.classList.remove('spawn');
                }, 200);
            }

            if (tile.merge) {
                window.setTimeout(function() {
                    el.classList.remove('merge');
                }, 200);
            }
        });

        Array.from(boardEl.querySelectorAll('.tile')).forEach(function(el) {
            if (!activeIds.has(el.dataset.id)) {
                el.remove();
            }
        });

        document.getElementById('score').textContent = this.score;
        
        if (this.gameOver) {
            document.getElementById('gameOverMessage').classList.add('show');
        } else {
            document.getElementById('gameOverMessage').classList.remove('show');
        }
    }
}

var game = null;

document.addEventListener('DOMContentLoaded', function() {
    game = new Game2048();
});

document.addEventListener('keydown', function(e) {
    if (!game) return;
    if (e.key === 'ArrowLeft') { e.preventDefault(); game.move('left'); }
    else if (e.key === 'ArrowRight') { e.preventDefault(); game.move('right'); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); game.move('up'); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); game.move('down'); }
    else if (e.key.toLowerCase() === 'r') { game.init(); }
});
</script>
@endsection
