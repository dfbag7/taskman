<?php namespace Dimbo\Taskman;

interface ScheduledTasksManagerInterface
{
    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     * @param \DateInterval $interval the interval should be less than one day
     *
     * @return bool True on success.
     */
    public function createDailyTask($name, $program, $arguments, $interval);

    /**
     * @param string $name      Name of the task
     * @param string $program   Full path to a program to execute.
     * @param string $arguments Command line arguments for the program.
     *
     * @return bool True on success.
     */
    public function createWeeklyTask($name, $program, $arguments);

    /**
     * @return array Array of task names
     */
    public function getTasks();

    /**
     * @param $name
     *
     * @return bool True if the task was existing.
     */
    public function deleteTask($name);

    /**
     * @param string $name
     * @param bool $enable
     *
     * @return bool True if the task exists and was disabled.
     */
    public function enableTask($name, $enable);
}
