<?php

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameBet;

class GameSeed extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        for($i = 0; $i < $limit; $i++){
        $game1 = Game::create(
            [
                'name' => '2+2 Challenge',
                'ru_name' => '2+2 Challenge',
                'logo' => 'math.jpg',
                'need_users' => 2,
                'description' => 'Докажи, что не просто просиживал штаны в школе! Побеждай игроков со всего мира. Выбери правильный ответ! Звучит просто? Поверь, это не так! Набери как можно больше очков',
                'slug' => '22',
                'status' => true
            ]);
        $game2 = Game::create(
            [
                'name' => 'Bomberman',
                'ru_name' => 'Bomberman',
                'logo' => 'bomber.jpg',
                'need_users' => 2,
                'description' => 'Тот самый бомбермен, только в 100 раз интереснее! Ставь бомбочки, загони противника в угол!',
                'slug' => 'bomber',
                'status' => false
            ]);
        $game3 = Game::create(
            [
                'name' => 'Battle Ship',
                'ru_name' => 'Морской Бой',
                'logo' => 'mb.jpg',
                'need_users' => 2,
                'description' => 'Тот самый морской бой, только в 100 раз интереснее! Расставь свои корабли и вычисли, где находяться корабли соперника! Огонь! ',
                'slug' => 'mb',
                'status' => false
            ]);
        $game4 = Game::create(
                [
                    'name' => 'cs-go',
                    'ru_name' => 'CS GO',
                    'logo' => 'cs-go.jpg',
                    'need_users' => 2,
                    'description' => 'козалось бы, куда лучше) Но самый популярный шутер теперь и у нас, играй 1 на 1 или собери команду и сразись 5х5',
                    'slug' => 'cs-go',
                    'status' => false
                ]);
        $game5 = Game::create(
            [
                'name' => 'Flip Master',
                'ru_name' => 'Мастер переворотов',
                'logo' => 'flipmaster.jpg',
                'need_users' => 2,
                'description' => 'Мастер переворотов c пользовательским физическим движком и анимированной физикой тряпичной куклы, является самым динамичным и интересным опытом с батутом когда-либо созданных! Бросьте вызов законам физики и докажите, что вы достойны и стань хозяином бабута!',
                'slug' => 'flip-master',
                'status' => false
            ]);
        $game6 = Game::create(
            [
                'name' => 'Table Tennis',
                'ru_name' => 'Настольный теннис',
                'logo' => 'tabletennis.jpg',
                'need_users' => 2,
                'description' => 'По силам ли Вам одержать победу над чемпионами в этой невероятной реалистичной теннисной игре? Вызывай противников и покажи невероятные техники владения теннисной ракеткой, и пусть весь мир подождет!',
                'slug' => 'table-tennis',
                'status' => false
            ]);
        $game7 = Game::create(
            [
                'name' => 'Hockey',
                'ru_name' => 'Хоккей',
                'logo' => 'hockey.jpg',
                'need_users' => 2,
                'description' => 'Забейте как можно большее количество голов в поединке с соперниками. Разыгрывай шайбу, применяйте силовые приемы, ускоряйся и делай невероятные финты, чтобы пробить вратаря соперника! Под твоим натиском должен сломаться любой.',
                'slug' => 'hockey',
                'status' => false
            ]);
        $game8 = Game::create(
            [
                'name' => 'Sudoku',
                'ru_name' => 'Судоку',
                'logo' => 'sudoku.jpg',
                'need_users' => 2,
                'description' => 'Поиграйте в эту фантастическую игру-головоломку, покоряющую мир новой головоломкой каждый день! Докажи что твой мозг быстрее обрабатывает информация и закрой все поле первым! Думай и побеждай!',
                'slug' => 'sudoku',
                'status' => false
            ]);
        $game9 = Game::create(
            [
                'name' => 'Motocross',
                'ru_name' => 'Мотокросс',
                'logo' => 'motocross.jpg',
                'need_users' => 2,
                'description' => 'Осторожно, Мотокросс пробудит в тебе жажду скорости! Здесь тебе предстоит гонять на мотоцикле по разным трассам и видам местности и выполнять головоломные трюки, оставляя соперников позади. ',
                'slug' => 'motocross',
                'status' => false
            ]);
        $game10 = Game::create(
            [
                'name' => 'Checkers',
                'ru_name' => 'Шашки',
                'logo' => 'check.jpg',
                'need_users' => 2,
                'description' => 'настольная игра для двух игроков, заключающаяся в передвижении определённым образом фишек-шашек по клеткам шашечной доски. Возьми все шашки соперника или лиши их возможности хода. Быстрое и увлекательное занятие, чтобы решить кто из вас лучше!',
                'slug' => 'checkers',
                'status' => false
            ]);
        $game11 = Game::create(
            [
                'name' => 'Tanks',
                'ru_name' => 'Танки',
                'logo' => 'tankattack3d.jpg',
                'need_users' => 2,
                'description' => 'В этой позиционной войне ты начинаешь битву на передовой линии. Твоя бронированная машина готова возглавить танковую атаку: разрушай бункеры, машины и бомбардировщики, ломай вражеские радары. В этой игре нужно будет, находясь в самом пылу боя, помочь своей армии выиграть войну.',
                'slug' => 'tanks',
                'status' => false
            ]);
        $game12 = Game::create(
            [
                'name' => 'Kranker',
                'ru_name' => 'Кранкер',
                'logo' => 'Kranker.jpg',
                'need_users' => 2,
                'description' => 'Кранкер - многопользовательский шутер от первого лица в пиксельной Вселенной. Заходи и выживи!',
                'slug' => 'kranker',
                'status' => false
            ]);
        $game13 = Game::create(
            [
                'name' => '8Pool',
                'ru_name' => 'Пул',
                'logo' => '8Pool.jpg',
                'need_users' => 2,
                'description' => 'Забей восемь шаров в поединке с живыми игроками. Чтобы выиграть, ЗАГОНИ В ЛУЗУ 8-ой ШАР ПОСЛЕДНИМ, после всех других шаров.',
                'slug' => '8pool',
                'status' => true
            ]);
        $game14 = Game::create(
            [
                'name' => 'Bowling King',
                'ru_name' => 'Король Боулинга',
                'logo' => 'Bowlingking.jpg',
                'need_users' => 2,
                'description' => 'Побеждай игроков со всего мира и стань королем боулинга! Сделай больше всех страйков и набирай максимальное количество очков среди своих друзьями',
                'slug' => 'bowlingking',
                'status' => true
            ]);
        $game15 = Game::create(
            [
                'name' => 'GoBattle',
                'ru_name' => 'ГоБатл',
                'logo' => 'Gobattle.jpg',
                'need_users' => 2,
                'description' => 'Сражайся чтобы быть королем! В этой массивной многопользовательской игре, основанной на 2D аркадных игр, вы должны бороться против других рыцарей, чтобы быть королем.',
                'slug' => 'gobattle',
                'status' => true
            ]);
        $game16 = Game::create(
            [
                'name' => 'Football Strike',
                'ru_name' => 'Футбольный Удар',
                'logo' => 'Footballstrike.jpg',
                'need_users' => 2,
                'description' => 'Вы никогда не играли в такой футбол до этого. Вызови своих друзей в мультиплеере, что бы определить кто лучше из вас при пробитие пенальти или выбивании целей на время.',
                'slug' => 'footballstrike',
                'status' => true
            ]);
        $game17 = Game::create(
            [
                'name' => 'Sleigh Shot',
                'ru_name' => 'Санный Выстрел',
                'logo' => 'Sleighshot.jpg',
                'need_users' => 2,
                'description' => 'Лето здесь, Санты нигде не видно, но новые претенденты теперь принимают вызов кто Выстрелит Санями дальше всех! ',
                'slug' => 'sleighshot',
                'status' => true
            ]);
        $game18 = Game::create(
            [
                'name' => 'Powerline',
                'ru_name' => 'Электрическая Змейка',
                'logo' => 'Powerline.jpg',
                'need_users' => 2,
                'description' => 'Современный подход к классической игре змейка. Ешьте, чтобы расти, приближайтесь к другим вражеским линиям, генерируйте электричество и повышайте свою линию, чтобы получить преимущество над другими игроками, но не позволяйте своей голове касаться других линий электропередач.',
                'slug' => 'powerline',
                'status' => true
            ]);
        $game19 = Game::create(
            [
                'name' => 'Darts of Fury',
                'ru_name' => 'Дартс Ярости',
                'logo' => 'Dartsoffury.jpg',
                'need_users' => 2,
                'description' => 'Давайте играть в дартс! Соревнуйтесь с реальными противниками в этой потрясающей многопользовательской игре в дартс. "Дартс ярости" футуристическая, современная игра сделана для дартс новичков и поклонников.',
                'slug' => 'dartsoffury',
                'status' => true
            ]);
        $game20 = Game::create(
            [
                'name' => 'CarJack',
                'ru_name' => 'Карджек',
                'logo' => 'Carjack.jpg',
                'need_users' => 2,
                'description' => 'Кэрджек - это интенсивная онлайн-гоночная игра, в которой вы мчитесь по шоссе на смешных скоростях против других игроков. Вы ускоряетесь от полицейских на шоссе, полном движения, как это час пик, убедитесь, что не врезаться в противном случае полиция быстро догнать вас в кратчайшие сроки.',
                'slug' => 'carjack',
                'status' => true
            ]);

        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 0
        ]);
        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 10
        ]);
        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 25
        ]);
        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 50
        ]);
        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 100
        ]);
        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 200
        ]);
        GameBet::create([
            'game_id' => $game1->id,
            'bet' => 500
        ]);

//                if(!$gameBet){
//                    GameBet::create([
//                        'game_id' => $game1->id,
//                        'bet' => $bet
//                    ]);
//                }





    }
}
