import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RegistroDocente } from './registro-docente';

describe('RegistroDocente', () => {
  let component: RegistroDocente;
  let fixture: ComponentFixture<RegistroDocente>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RegistroDocente],
    }).compileComponents();

    fixture = TestBed.createComponent(RegistroDocente);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
