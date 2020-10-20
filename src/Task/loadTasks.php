<?php

namespace Generoi\Robo\Task;

trait loadTasks
{
    // Sub tasks
    use Git\loadTasks;
    use GitHub\loadTasks;
    use Npm\loadTasks;
    use PhpCodeSniffer\loadTasks;
    use Placeholder\loadTasks;
    use Remote\loadTasks;
    use Wp\loadTasks;
    use Yaml\loadTasks;
}
