@servers(['psilocin' => ['psilocin.empinet.com']])

@task('deploy')
    cd /srv/stacks/project-echo
    git stash
    git pull origin master
    docker exec echo-api composer install
@endtask
