<?php

namespace tests\unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TKuni\AnkiCardGenerator\App;
use TKuni\AnkiCardGenerator\Domain\Models\Card;
use TKuni\AnkiCardGenerator\Domain\Models\Github\Comment;
use TKuni\AnkiCardGenerator\Domain\Models\Github\Issue;
use TKuni\AnkiCardGenerator\Domain\ObjectValues\EnglishText;
use TKuni\AnkiCardGenerator\Infrastructure\GithubAdapter;
use TKuni\AnkiCardGenerator\Infrastructure\interfaces\IAnkiWebAdapter;
use TKuni\AnkiCardGenerator\Infrastructure\interfaces\IGithubAdapter;
use TKuni\AnkiCardGenerator\Infrastructure\interfaces\IProgressRepository;
use TKuni\AnkiCardGenerator\Infrastructure\interfaces\ITranslateAdapter;

class AppTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function run_()
    {
        #
        # Prepare
        #
        $ankiWebMock = \Mockery::mock(IAnkiWebAdapter::class);
        $ankiWebMock->shouldReceive('saveCard')->once()->withArgs(function($deck, Card $card) {
            return $card->front() === 'title'
                && $card->back() === 'タイトル';
        });
        $ankiWebMock->shouldReceive('saveCard')->once()->withArgs(function($deck, Card $card) {
            return $card->front() === 'apple'
                && $card->back() === 'りんご';
        });
        $ankiWebMock->shouldReceive('saveCard')->once()->withArgs(function($deck, Card $card) {
            return $card->front() === 'body'
                && $card->back() === '本文';
        });
        $ankiWebMock->shouldReceive('saveCard')->once()->withArgs(function($deck, Card $card) {
            return $card->front() === 'grape'
                && $card->back() === 'ブドウ';
        });
        $ankiWebMock->shouldReceive('saveCard')->once()->withArgs(function($deck, Card $card) {
            return $card->front() === 'comment-body'
                && $card->back() === 'コメント本文';
        });
        $ankiWebMock->shouldReceive('saveCard')->once()->withArgs(function($deck, Card $card) {
            return $card->front() === 'tomato'
                && $card->back() === 'トマト';
        });
        $ankiWebMock->shouldReceive('login')->andReturnUndefined();
        app()->bind(IAnkiWebAdapter::class, function() use ($ankiWebMock) {
            return $ankiWebMock;
        });

        $githubMock = \Mockery::mock(IGithubAdapter::class);
        $githubMock->shouldReceive('fetchIssues')->andReturnUsing(function($username, $repository) {
            return [
                new Issue(
                    $username,
                    $repository,
                    1,
                    new EnglishText('title. apple.'),
                    new EnglishText('body. grape.'),
                ),
            ];
        });
        $githubMock->shouldReceive('fetchComments')->andReturnUsing(function() {
            return [
                new Comment(new EnglishText('comment-body. tomato'))
            ];
        });
        app()->bind(IGithubAdapter::class, function() use ($githubMock) {
            return $githubMock;
        });

        $translateMock = \Mockery::mock(ITranslateAdapter::class);
        $translateMock->shouldReceive('translate')->andReturnUsing(function($text) {
            switch ($text) {
                case 'title':
                    return 'タイトル';
                case 'apple':
                    return 'りんご';
                case 'body':
                    return '本文';
                case 'comment-body':
                    return 'コメント本文';
                case 'grape':
                    return 'ブドウ';
                case 'tomato':
                    return 'トマト';
                default:
                    throw new \Exception('意図しない入力:' . $text);
                    break;
            }
        });
        app()->bind(ITranslateAdapter::class, function() use ($translateMock) {
            return $translateMock;
        });

        $progressRepoMock = \Mockery::mock(IProgressRepository::class);
        $progressRepoMock->shouldReceive('save')->andReturnUndefined();
        $progressRepoMock->shouldReceive('findByIssue')->andReturnUsing(function($text) {
            return null;
        });
        app()->bind(IProgressRepository::class, function() use ($progressRepoMock) {
            return $progressRepoMock;
        });

        #
        # Run
        #
        app()->make('app')->run();

        #
        # Assertion
        #
        $this->assertTrue(true);
    }
}