import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

test('main navigational lists share a pagination component', () => {
    const pagination = fs.readFileSync('resources/js/components/layout/PaginationNav.vue', 'utf8')

    assert.match(pagination, /defineProps\(\{/)
    assert.match(pagination, /resource:/)
    assert.match(pagination, /noun:/)
    assert.match(pagination, /resource\.last_page > 1/)
    assert.match(pagination, /v-for="page in resource\.links"/)
})

test('project, task, team, and project detail pages use the shared pagination component', () => {
    const pages = [
        'resources/js/pages/Project/Index.vue',
        'resources/js/pages/Task/Index.vue',
        'resources/js/pages/Team/Index.vue',
        'resources/js/pages/Project/Show.vue',
    ]

    for (const path of pages) {
        const page = fs.readFileSync(path, 'utf8')

        assert.match(
            page,
            /import PaginationNav from ['"]@\/components\/layout\/PaginationNav\.vue['"]/,
        )
        assert.match(page, /<PaginationNav/)
    }
})

test('project detail uses a paginated task collection instead of rendering project.tasks directly', () => {
    const page = fs.readFileSync('resources/js/pages/Project/Show.vue', 'utf8')

    assert.match(page, /tasks:\s*Object/)
    assert.match(page, /tasks\.data\.length/)
    assert.match(page, /v-for="\s*task in tasks\.data\s*"/)
    assert.doesNotMatch(page, /v-for="\s*task in project\.tasks\s*"/)
})

test('project controller paginates tasks for the detail page', () => {
    const controller = fs.readFileSync('app/Http/Controllers/ProjectController.php', 'utf8')

    assert.match(controller, /Inertia::render\('Project\/Show'/)
    assert.match(
        controller,
        /'tasks'\s*=>\s*\$project->tasks\(\)->with\('assignee'\)->latest\(\)->paginate\(10\)/,
    )
})
