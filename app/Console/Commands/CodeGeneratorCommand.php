<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use VictorYoalli\LaravelCodeGenerator\Facades\CodeGenerator;
use VictorYoalli\LaravelCodeGenerator\Facades\ModelLoader;
use VictorYoalli\LaravelCodeGenerator\Structure\Model;

class CodeGeneratorCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:generator {model : Model(s) separated by commas: i.e: \'User, Post, Section\' } ' .
            '{--namespace=App\Models : Models Namesspace} ' .
            '{--w|views : View files} ' .
            '{--c|controller : Controller} ' .
            '{--a|api : Creates API Controller} ' .
            '{--r|routes : Display Routes} ' .
            '{--l|lang : Language} ' .
            '{--A|all : All Files}' .
            '{--f|factory : Factory} ' .
            '{--t|tests : Feature Test} ' .
            '{--auth : Auth (not included in all)} ' .
            '{--event= : Event (not included in all)} ' .
            '{--notification= : Notification (not included in all)} ' .
            '{--F|force : Overwrite files if exists} ' .
            '{--livewire : Add livewire files}' .
            '{--theme=blade : Theme}';

    protected $description = 'Multiple files generation';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');

        //Options
        $controller = $this->option('controller');
        $routes = $this->option('routes');
        $views = $this->option('views');
        $api = $this->option('api');
        $lang = $this->option('lang');
        $factory = $this->option('factory');
        $tests = $this->option('tests');
        $auth = $this->option('auth');
        $event = $this->option('event');
        $notification = $this->option('notification');
        $all = $this->option('all');
        $livewire = $this->option('livewire');
        $theme = $this->option('theme');
        if ($all) {
            $lang = $controller = $routes = $views = $all;
        }
        $request = ($controller || $api);

        $options = compact(['factory', 'controller', 'routes', 'views',  'api', 'tests', 'auth', 'request', 'notification', 'event', 'lang','livewire']);
        $namespace = rtrim($this->option('namespace'), '\\');
        $models = collect(explode(',', $this->argument('model')));

        $models->each(function ($model) use ($namespace, $options, $theme, $force) {
            $model = "{$namespace}\\{$model}";
            if (!$model) {
                return;
            }
            $m = ModelLoader::load($model);

            $this->generate($m, $options, $theme, $force);
        });
    }

    public function generate(Model $m, $options, $theme, $force)
    {
        $option = (object) $options;
        $folder = str($m->name)->plural()->snake();

        $this->info('🚀 Starting code generation');
        $this->newLine();
        if ($option->controller) {
            $this->printif('Web Controller', CodeGenerator::generate($m, $theme . '/Http/Controllers/ModelController', "app/Http/Controllers/{$m->name}Controller.php", $force, $options));
        }
        if ($option->api) {
            $this->printif('API Controller', CodeGenerator::generate($m, $theme . '/Http/Controllers/API/ModelController', "app/Http/Controllers/API/{$m->name}Controller.php", $force, $options));
        }
        if ($option->request) {
            $this->printif('Form Request', CodeGenerator::generate($m, $theme . '/Http/Requests/ModelRequest', "app/Http/Requests/{$m->name}Request.php", $force, $options));
        }

        if ($option->views) {
            $this->printif('Index View', CodeGenerator::generate($m, $theme . '/views/index', "resources/views/{$folder}/index.blade.php", $force, $options));
            $this->printif('Create View', CodeGenerator::generate($m, $theme . '/views/create', "resources/views/{$folder}/create.blade.php", $force, $options));
            $this->printif('Show View', CodeGenerator::generate($m, $theme . '/views/show', "resources/views/{$folder}/show.blade.php", $force, $options));
            $this->printif('Edit View', CodeGenerator::generate($m, $theme . '/views/edit', "resources/views/{$folder}/edit.blade.php", $force, $options));
        }
        if ($option->lang) {
            $this->printif('Lang', CodeGenerator::generate($m, $theme . '/lang/en/Models', "resources/lang/en/{$folder}.php", $force, $options));
        }
        if ($option->factory) {
            $this->printif('Factory ', CodeGenerator::generate($m, $theme . '/database/factories/ModelFactory', "database/factories/{$m->name}Factory.php", $force, $options));
        }
        if ($option->tests) {
            $this->printif('Feature Test Controller', CodeGenerator::generate($m, $theme . '/tests/Feature/Http/Controllers/ModelControllerTest', "tests/Feature/Http/Controllers/{$m->name}ControllerTest.php", $force, $options));
            if ($option->controller) {
                $this->printif('Feature Test Controller', CodeGenerator::generate($m, $theme . '/tests/Feature/Http/Controllers/ModelControllerTest', "tests/Feature/Http/Controllers/{$m->name}ControllerTest.php", $force, $options));
            }
            if ($option->api) {
                $this->printif('Feature Test API Controller', CodeGenerator::generate($m, $theme . '/tests/Feature/Http/Controllers/API/ModelControllerTest', "tests/Feature/Http/Controllers/API/{$m->name}ControllerTest.php", $force, $options));
            }
        }
        if ($option->notification) {
            $this->printif('Notification', CodeGenerator::generate($m, $theme . '/Notifications/ModelNotification', "app/Notifications/{$m->name}{$option->notification}.php", $force, $options));
        }
        if ($option->event) {
            $this->printif('Event', CodeGenerator::generate($m, $theme . '/Events/ModelEvent', "app/Events/{$m->name}{$option->event}.php", $force, $options));
        }
        if ($option->livewire) {
            $plural = str($m->name)->plural();
            $this->printif('Livewire Component ', CodeGenerator::generate($m, "/livewire/Http/Index", "app/Http/Livewire/{$plural}/Index.php", $force, $options));
            $this->printif('Livewire index view ', CodeGenerator::generate($m, "/livewire/views/index", "resources/views/livewire/{$folder}/index.blade.php", $force, $options));
            $this->printif('Livewire list view ', CodeGenerator::generate($m, "/livewire/views/list", "resources/views/livewire/{$folder}/list.blade.php", $force, $options));
            $this->printif('Livewire edit view ', CodeGenerator::generate($m, "/livewire/views/edit", "resources/views/livewire/{$folder}/edit.blade.php", $force, $options));
            $this->printif('Livewire show view ', CodeGenerator::generate($m, "/livewire/views/show", "resources/views/livewire/{$folder}/show.blade.php", $force, $options));
        }
        if ($option->routes) {
            $this->newLine(3);
            $this->line('<fg=cyan>'.CodeGenerator::generate($m, $theme . '/routes', null, $force, $options).'</>');
        }

        $this->newLine();
        $this->info('🎉 Finished!');
    }

    public function printif($type, $filename)
    {
        $text = empty($filename) ? '<fg=red> ✖ </> '. $type . '<fg=yellow> already exists </>' : '<fg=green>✔</> <fg=default>' . $filename . '<fg=magenta> created. </>';
        $this->line($text);
    }
}