<?php namespace Dimbo\Taskman;

class LinuxTaskScheduler implements ScheduledTasksManagerInterface
{
    const CRONTAB_FILE = '/etc/crontab';

    /** @var string[] Loaded crontab' lines */
    private $lines;

    /**
     * LinuxTaskScheduler constructor.
     */
    public function __construct()
    {
        $this->lines = $this->loadCrontab();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->saveCrontab($this->lines);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function loadCrontab()
    {
        $lines = @file(self::CRONTAB_FILE, FILE_IGNORE_NEW_LINES);
        if($lines === false)
            throw new \Exception('Cannot load ' . self::CRONTAB_FILE);

        return $lines;
    }

    /**
     * @param array $lines
     *
     * @throws \Exception
     */
    private function saveCrontab(array $lines)
    {
        $result = @file_put_contents(self::CRONTAB_FILE, join(PHP_EOL, $lines) . PHP_EOL);
        if($result === false)
            throw new \Exception('Cannot write ' . self::CRONTAB_FILE);
    }

    /**
     * @param string $name
     *
     * @return int|false
     */
    private function findTaskByName($name)
    {
        $result = false;

        foreach($this->lines as $idx => $line)
        {
            if(preg_match('/^\s*\# tFLOW task\s+' . preg_quote($name) . '\s*$/i', $line) === 1)
            {
                $result = $idx;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $definition
     *
     * @return bool
     */
    private function addOrReplaceTask($name, $definition)
    {
        $idx = $this->findTaskByName($name);
        if($idx === false)
        {
            $idx = count($this->lines) + 1;
        }

        $this->lines[$idx] = '# tFLOW task ' . $name;
        $this->lines[$idx+1] = $definition;

        return true;
    }

    /**
     * @param string $name            Name of the task
     * @param string $program         Full path to a program to execute.
     * @param string $arguments       Command line arguments for the program.
     *
     * @return bool True on success.
     */
    public function createDailyTask($name, $program, $arguments)
    {
        return $this->addOrReplaceTask($name, join("\t", [
            '0', '0', '*', '*', '*',
            'www-data',
            $program . ' ' . $arguments
        ]));
    }

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     *
     * @return bool True on success.
     */
    public function createMinuteTask($name, $program, $arguments)
    {
        return $this->addOrReplaceTask($name, join("\t", [
            '*', '*', '*', '*', '*',
            'www-data',
            $program . ' ' . $arguments
        ]));
    }

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     *
     * @return bool True on success.
     */
    public function createWeeklyTask($name, $program, $arguments)
    {
        return $this->addOrReplaceTask($name, join("\t", [
            '1', '0', '*', '*', '6',
            'www-data',
            $program . ' ' . $arguments
        ]));
    }

    /**
     * @return array Array of task names
     */
    public function getTasks()
    {
        $result = [];

        foreach($this->lines as $idx => $line)
        {
            if(preg_match('/^\s*\# tFLOW task\s*(?<task_name>\S+)\s*$/i', $line, $matches) === 1)
            {
                if( array_key_exists('task_name', $matches) )
                {
                    $result[] = $matches['task_name'];
                }
            }
        }

        return $result;
    }

    /**
     * @param $name
     *
     * @return bool True if the task was existing.
     */
    public function deleteTask($name)
    {
        $idx = $this->findTaskByName($name);
        if($idx !== false)
        {
            unset($this->lines[$idx]);
            unset($this->lines[$idx+1]);

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param bool $enable
     *
     * @return bool True if status of the task has been actually changed.
     */
    public function enableTask($name, $enable)
    {
        $result = false;

        $idx = $this->findTaskByName($name);
        if($idx !== false)
        {
            if($enable)
            {
                if($this->lines[$idx+1][0] === '#')
                {
                    $this->lines[$idx+1] = substr($this->lines[$idx+1], 1);
                    $result = true;
                }
            }
            else
            {
                if($this->lines[$idx+1][0] !== '#')
                {
                    $this->lines[$idx+1] = '#' . $this->lines[$idx+1];
                    $result = true;
                }
            }
        }

        return $result;
    }
}
