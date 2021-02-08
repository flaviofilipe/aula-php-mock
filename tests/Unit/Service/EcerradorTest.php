<?php

namespace Alura\Leilao\Tests\Service;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
use Alura\Leilao\Model\Leilao;
use Alura\Leilao\Service\Encerrador;
use Alura\Leilao\Service\EnviadorEmail;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EncerradorTest extends TestCase
{

   /** @var Encerrador */ 
    private $encerrador;
    /** @var MockObject */
    private $enviadorEmail;
    private $fiat147;
    private $variant;

    protected function setUp(): void
    {
        $this->fiat147 = new Leilao(
            'Fiat 147 0KM',
            new DateTimeImmutable('8 days ago')
        );
        $this->variant = new Leilao(
            'Variant 1972 0KM',
            new DateTimeImmutable('10 days ago')
        );

        /**
         * Criar uma classe "falsa" a partir de LeilaoDao
         * createMock(LeilaoDao::class)
         */
        $leilaoDao = $this->createMock(LeilaoDao::class);

        /**
         * Criar um "gerenciador de mock"
         * podendo definir vários parâmetros
         */
        // $leilaoDao = $this->getMockBuilder(LeilaoDao::class)
        // ->getMock();

        /**
         * Criar uma classe "falsa" a partir de LeilaoDao
         * createMock(LeilaoDao::class)
         */
        $leilaoDao = $this->createMock(LeilaoDao::class);

        /**
         * Criar um "gerenciador de mock"
         * podendo definir vários parâmetros
         */
        // $leilaoDao = $this->getMockBuilder(LeilaoDao::class)
        // ->getMock();

        $leilaoDao->method('recuperarNaoFinalizados')
            ->willReturn([$this->fiat147, $this->variant]);

        $leilaoDao->method('recuperarFinalizados')
            ->willReturn([$this->fiat147, $this->variant]);
        
        /**
         * Chamando o método 2x seguidas
         * 
         * Para chamar 1x existe o método once()
         */

        $leilaoDao->expects($this->exactly(2))
        ->method('atualiza')
        ->withConsecutive(
            [$this->fiat147],
            [$this->variant]
        );

        $this->enviadorEmail = $this->createMock(EnviadorEmail::class);
        $this->encerrador = new Encerrador($leilaoDao, $this->enviadorEmail);
    }

    public function testLeiloesComMaisDeUmaSemanaDEvemSerEncerrados()
    {
        
        $this->encerrador->encerra();

        $leiloes = [$this->fiat147, $this->variant];
        self::assertTrue($leiloes[0]->estaFinalizado());
        self::assertTrue($leiloes[1]->estaFinalizado());
        self::assertCount(2, $leiloes);
    }

    /**
     * Teste que espera uma exceção
     *
     * @return void
     */
    public function testDeveContiniarOProcessamentoAoEncontrarErroAoEnviarEmail()
    {
        $e = new DomainException('Erro ao enviar e-mail');
        $this->enviadorEmail->expects($this->exactly(2))
        ->method('notificarTerminoLeilao')
        ->willThrowException($e);

        $this->encerrador->encerra();
    }

    /**
     * Testar parametros
     *
     * @return void
     */
    public function testSoDeveEnviarLeilaoPorEmailAposFinalizado()
    {
        $this->enviadorEmail->expects($this->exactly(2))
        ->method('notificarTerminoLeilao')
        ->willReturnCallback(function (Leilao $leilao){
            static::assertTrue($leilao->estaFinalizado());
        });

        $this->encerrador->encerra();
    }
}
