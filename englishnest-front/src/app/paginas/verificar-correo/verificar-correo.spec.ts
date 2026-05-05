import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VerificarCorreo } from './verificar-correo';

describe('VerificarCorreo', () => {
  let component: VerificarCorreo;
  let fixture: ComponentFixture<VerificarCorreo>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [VerificarCorreo],
    }).compileComponents();

    fixture = TestBed.createComponent(VerificarCorreo);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
