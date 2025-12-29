<?php

namespace App\Console\Commands;

use App\Contracts\Symbol;
use App\Decks\EmojiDeck;
use App\Games\SoloGame;
use App\Player;
use Illuminate\Console\Command;

class SoloGameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:solo-game';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Solo game';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $game = new SoloGame(EmojiDeck::generate(), new Player('Prince', 'Prince'));

        $game->start();

        while(! $game->isOver()){

            dump($game->getStatus());

            $this->line(PHP_EOL.'WELL');
            $this->line(collect($game->peak()->getSymbols())->map(fn(Symbol $symbol) => $symbol->render())->implode('    '));


            $this->line(PHP_EOL.'HAND');
            $this->line(collect($game->getPlayer()->peak()->getSymbols())->map(fn(Symbol $symbol) => $symbol->render())->implode('    '));

            $symbol = $this->ask("Symbol");

            $game->spotted($symbol);
        }

        $this->info('You took: '.$game->getDuration().' seconds');
    }
}
