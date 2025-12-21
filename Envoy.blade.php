@servers(['psilocin' => ['psilocin.empinet.com']])

@task('deploy')
    cd /srv/stacks/project-echo
    git stash
    git pull origin master
    docker exec echo-api composer install
    docker exec echo-api php artisan migrate --force
    docker exec echo-api php artisan optimize
@endtask
