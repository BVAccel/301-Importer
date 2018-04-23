<?php namespace RedirectImport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;

use RedirectImport\Credentials;
use Tightenco\Collect\Support\Collection;
use Zttp\Zttp;

class GetCurrentRedirectsCommand extends Command {

    protected function configure(){
        $this->setName("Redirect:Get")
            ->setDescription("Gets all the current redirects from the shopify account.")
            ->addArgument('apiCredentials', InputArgument::REQUIRED, 'Shopify API String? (key:pass@domain)');
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        $creds = new Credentials( $input->getArgument('apiCredentials') );

        $url = "https://" . $creds->getCredString() . '/admin/redirects';
        $count = 0;
        $size = 250;

        $response = Zttp::get( $url . "/count.json" );
        $count = $response->json()['count'];

        $output->writeln( '<info>Found <options=bold>' . $count . '</> current redirects</info>' );

        if( $count === 0 ) {
            exit();
        }
        else {
            $output->writeln("");
            $redirects = $this->getRedirects( $count, $size, $url );
            $output->writeln( "<info>Here are the current redirects:</info>" );
            $table = new Table( $output );
            $table
                ->setHeaders( [ 'Count', 'ID', "Path", "Target" ] )
                ->setRows( $redirects->toArray() );
            $table->render();
        }
    }

    private function getRedirects( $count, $size, $url ) {
        $pages = intval( $count / $size ) + 1;
        $rows = [];

        while( $pages > 0 ) {
            $response = Zttp::get( $url . ".json?limit=$size&page=$pages");
            $redirects = $response->json();
            $rows = array_merge( $rows, $redirects['redirects'] );
            $pages -= 1;
        }
        $rows = new Collection( $rows );
        return $rows->map( function( $item, $index ) {
            return [ $index+1, $item['id'], $item['path'], $item['target'] ];
        } );
    }
}
