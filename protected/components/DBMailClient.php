<?php

class DBMailClient extends CComponent
{
    public function init()
    {

    }

    /**
     * @param $userName string
     * @throws DBMailClientException
     * @return string
     */
    public function getScript($userName)
    {
        $userName = escapeshellarg($userName);
        $cmd = "EDITOR=cat dbmail-sievecmd -u $userName -e";
        $output = $this->exec($cmd);
        if (end($output) == 'No active script found!')
            return '';
        if (end($output) == 'File not modified, canceling.')
            throw new DBMailClientException("'$cmd' returned wrong output: ".implode("\n", $output));

        array_pop($output);

        return implode("\n", $output);
    }

    /**
     * @param $userName string
     * @param $script string
     */
    public function writeScript($userName, $script)
    {
        $userName = escapeshellarg($userName);
        $this->exec("dbmail-sievecmd -u $userName -r script.sieve", 'Script [script.sieve] deleted.');

        $tempFile = tempnam(sys_get_temp_dir(), 'sieve-');
        file_put_contents($tempFile, $script);
        $output = $this->exec("dbmail-sievecmd -u $userName -i script.sieve $tempFile -y");
        unlink($tempFile);

        if (strpos(end($output), 'marked inactive') !== false)
            $this->exec("dbmail-sievecmd -u $userName -a script.sieve", 'Script [test.sieve] is now active. All others are inactive.');
    }

    /**
     * @param $cmd string
     * @param null|string $expectedLastString
     * @throws DBMailClientException
     * @return string[]
     */
    protected function exec($cmd, $expectedLastString = null)
    {
        exec($cmd, $output, $returnVal);
        if ($returnVal)
            throw new DBMailClientException("'$cmd' returned code $returnVal with message: ".implode("\n", $output));
        if (!empty($expectedLastString) && end($output) != $expectedLastString)
            throw new DBMailClientException("'$cmd' returned wrong output: ".implode("\n", $output));

        return $output;
    }
}

class DBMailClientException extends CException {};