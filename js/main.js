// Project.
Vue.component('project', {
  props: ['project'],
  template: `
    <div class="project">
      <div class="project__title">
        {{ project.title }}
      </div>
    </div>
  `
});