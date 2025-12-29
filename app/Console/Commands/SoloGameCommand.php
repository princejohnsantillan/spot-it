<?php

namespace App\Console\Commands;

use App\Contracts\Symbol;
use App\Decks\EmojiDeck;
use App\Games\SoloGame;
use App\Player;
use Illuminate\Console\Command;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

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
        $deck = new EmojiDeck;

        $game = new SoloGame($deck->generate(), new Player('Solo', 'Solo'));

        $game->start();

        while (! $game->isOver()) {
            clear();

            table(
                headers: ['Well', 'Hand'],
                rows: [$game->getStatus()]
            );

            info(PHP_EOL.'WELL');
            info(collect($game->peak()->getSymbols())->map(fn (Symbol $symbol) => $symbol->render())->implode('    '));

            info(PHP_EOL.'HAND');
            info(collect($game->getPlayer()->peak()->getSymbols())->map(fn (Symbol $symbol) => $symbol->render())->implode('    '));

            $symbol = $deck->find(trim(text('Symbol', required: true)));

            if ($symbol !== null) {
                $game->spotted($symbol);
            }
        }

        info('You took: '.$game->getDuration().' seconds');
    }
}
