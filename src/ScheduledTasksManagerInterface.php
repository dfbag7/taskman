<?php namespace Dimbo\Taskman;

interface ScheduledTasksManagerInterface
{
    /**
     * @param string $name Name of the task
     * @param string $program Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     * @param \DateInterval $interval
     *
     * @return mixed
     */
    public function createTask($name, $program, $arguments, $interval);

    /**
     * @return array
     */
    public function getTasks();

    /**
     * @param $name
     *
     * @return mixed
     */
    public function deleteTask($name);
}
