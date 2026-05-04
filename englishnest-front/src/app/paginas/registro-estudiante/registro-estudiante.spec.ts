import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RegistroEstudiante } from './registro-estudiante';

describe('RegistroEstudiante', () => {
  let component: RegistroEstudiante;
  let fixture: ComponentFixture<RegistroEstudiante>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RegistroEstudiante],
    }).compileComponents();

    fixture = TestBed.createComponent(RegistroEstudiante);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
