<?php namespace RedirectImport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Tightenco\Collect\Support\Collection;
use Zttp\Zttp;

use League\Csv\Statement;
use League\Csv\Reader;

class ImportRedirectsCommand extends Command {

    protected function configure() {
        $this->setName( "Redirect:Import" )
            ->setDescription( "Imports redirects into a shopify account." )
            ->addArgument( 'apiCredentials', InputArgument::REQUIRED, 'Shopify API String? (key:pass@domain)' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {

        $creds = new Credentials( $input->getArgument( 'apiCredentials' ) );

        $url = "https://" . $creds->getCredString() . '/admin/redirects';

        $helper = $this->getHelper( 'question' );
        $question = new Question( "What is the absolute path to the file?\t" );
        $filename = $helper->ask( $input, $output, $question );
        $filename = realpath( $this->expand_tilde( $filename ) );
        if ( empty( $filename ) ) {
            $output->writeln( "<error>You must provide a filename.</error>" );
            exit( 1 );
        }

        $output->writeln( "\t<info>Parsing $filename</info>" );
        $imports = $this->parseFile( $filename );

        if( count( $imports ) == 0 ) {
            $output->writeln( "<error>There were no redirects found in the file.</error>" );
            exit( 1 );
        }

        $output->writeln( "\nI'm going to import the following entries: " );
        $output->writeln("");

        $table = new Table( $output );
        $table
            ->setHeaders( [ 'Old URL', 'New URL' ] )
            ->setRows( $imports->toArray() );
        $table->render();

        $question = new ConfirmationQuestion(
            "Continue with this action?\t",
            false,
            '/^(y)/i'
        );
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln( "Aborted\n");
            return;
        }

        $output->writeln("<comment>Starting the import</comment>" );

        $this->importRedirects( $output, $url, $imports );

        $output->writeln("\n<comment>Done.</comment>" );

        return;
    }

    private function expand_tilde( $path ) {
        if ( function_exists( 'posix_getuid' ) && strpos( $path, '~' ) !== false ) {
            $info = posix_getpwuid( posix_getuid() );
            $path = str_replace( '~', $info[ 'dir' ], $path );
        }
        return $path;
    }

    private function parseFile( $filename ) {
        $csv = Reader::createFromPath( $filename, 'r');
        $statement = (new Statement())->offset(1);
        $records = new Collection( $statement->process( $csv ) );
        return $records;
    }

    private function importRedirects( OutputInterface $output, $url, Collection $imports ) {
        $imports->each( function( $item, $index ) use( $output, $url ) {
            $output->write( "\t<comment>Creating redirect for #" . $index . " : " . $item[0] . " to " . $item[1] . "</comment>" );
            $response = Zttp::asJson()->post( $url . ".json", [
                "redirect" => [
                    "path" => $item[0],
                    "target" => $item[1],
                ]
            ] );
            $response = $response->json();
            if( array_key_exists( 'errors', $response ) ) {
                $output->write( "\t<error>ERROR</error>\n\t" );
                dump( $response );
            }
            else {
                $output->write( "\t<info>Success</info>" );
            }
            $output->write( "\n");
            sleep( 2 );
        } );
    }
}
